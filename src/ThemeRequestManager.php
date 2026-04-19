<?php

/**
 * ThemeRequestManager — installation request queue for themes.
 *
 * a non-admin user can submit a request (via zip upload or
 * via remote URL). an admin (settings.manage) can then review
 * liste, examiner the package and approuver/rejeter.
 *
 * Flux :
 * submit() : creates a entry pending, stocke the file en data/themes_pending/
 * listAll(): admin, lists all requests
 * approve() : admin, extracts + installed a theme safe then marque the request comme approved * reject() : admin, deletes pending package + marks rejected
 */
class ThemeRequestManager {

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const SOURCE_UPLOAD = 'upload';
    public const SOURCE_URL = 'url';

    /**
     * creates a nouvelle request. Appelee by a user authentifie.
     *
     * @param int $userId user demandeur
     * @param string $sourceType 'upload' | 'url'
     * @param string|null $tmpUploadPath path of file temporary (if upload)
     * @param string|null $sourceUrl URL remote (if url)     * @param string $themeName Nom propose
     * @param string $description Description libre
     *
     * @return array{ok:bool,errors?:string[],id?:int}
     */
    public static function submit(
        int $userId,
        string $sourceType,
        ?string $tmpUploadPath,
        ?string $sourceUrl,
        string $themeName,
        string $description
    ): array {
        if ($userId <= 0) {
            return ['ok' => false, 'errors' => ['Utilisateur invalide.']];
        }
        $sourceType = in_array($sourceType, [self::SOURCE_UPLOAD, self::SOURCE_URL], true) ? $sourceType : '';
        if ($sourceType === '') {
            return ['ok' => false, 'errors' => ['Type de source invalide.']];
        }

        $themeName = trim($themeName);
        if ($themeName === '' || mb_strlen($themeName) > 100) {
            return ['ok' => false, 'errors' => ['Nom du theme requis (1-100 caracteres).']];
        }
        $description = trim($description);
        if (mb_strlen($description) > 1000) {
            return ['ok' => false, 'errors' => ['Description trop longue (max 1000 caracteres).']];
        }

        $sourceFile = null;
        $finalUrl = null;

        if ($sourceType === self::SOURCE_UPLOAD) {
            if (!$tmpUploadPath || !is_file($tmpUploadPath)) {
                return ['ok' => false, 'errors' => ['Fichier upload invalide.']];
            }
            $stored = ThemePackage::storePendingFile($tmpUploadPath);
            if (!$stored['ok']) return $stored;
            $sourceFile = $stored['filename'];
        } else {
            $url = trim((string) $sourceUrl);
            if ($url === '' || mb_strlen($url) > 500) {
                return ['ok' => false, 'errors' => ['URL requise (max 500 caracteres).']];
            }
            if (!preg_match('#^https://#i', $url)) {
                return ['ok' => false, 'errors' => ['L URL doit commencer par https://.']];
            }
            $finalUrl = $url;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO theme_requests
                (requested_by, source_type, source_url, source_file, theme_name, theme_description, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$userId, $sourceType, $finalUrl, $sourceFile, $themeName, $description]);
        $newId = (int) $db->lastInsertId();

        // Notification : notify the users having of themes.manage        // selon the policy configuree in settings > Notifications.
        try {
            $requester = UserManager::getById($userId);
            $requesterName = trim(((string) ($requester['first_name'] ?? '')) . ' ' . ((string) ($requester['last_name'] ?? '')));
            if ($requesterName === '') {
                $requesterName = (string) ($requester['username'] ?? 'utilisateur');
            }
            Notifier::sendThemeRequestSubmitted([
                'id' => $newId,
                'theme_name' => $themeName,
                'theme_description' => $description,
                'source_type' => $sourceType,
                'source_url' => $finalUrl,
            ], $requesterName);
        } catch (Throwable $e) {
            // Do not block submission if notification fails.
            error_log('[ThemeRequest] notification failure: ' . $e->getMessage());
        }

        return ['ok' => true, 'id' => $newId];
    }

    /** Lists all requests (admin). */
    public static function listAll(?string $status = null): array {
        $db = Database::getInstance();
        $sql = "
            SELECT r.*, u.username AS requester_username,
                   u.first_name AS requester_first_name, u.last_name AS requester_last_name,
                   rv.username AS reviewer_username
            FROM theme_requests r
            LEFT JOIN users u ON u.id = r.requested_by
            LEFT JOIN users rv ON rv.id = r.reviewed_by
        ";
        $params = [];
        if ($status !== null) {
            $sql .= " WHERE r.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY r.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /** Requests of a given user (for their own view). */
    public static function listForUser(int $userId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM theme_requests
            WHERE requested_by = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function countPending(): int {
        $db = Database::getInstance();
        return (int) $db->query("SELECT COUNT(*) FROM theme_requests WHERE status = 'pending'")->fetchColumn();
    }

    public static function getById(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM theme_requests WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Approuve a request : extracts the package and the installed.
     * the admin is free-form of provide of notes (ex: "theme checks manuellement").
     *
     * @return array{ok:bool,errors?:string[],theme_id?:string}
     */
    public static function approve(int $requestId, int $reviewerId, string $notes = '', bool $overwrite = false): array {
        $req = self::getById($requestId);
        if ($req === null) {
            return ['ok' => false, 'errors' => ['Demande introuvable.']];
        }
        if ($req['status'] !== self::STATUS_PENDING) {
            return ['ok' => false, 'errors' => ['Cette demande a deja ete traitee.']];
        }

        // retrieves the package zip (upload or url)
        $zipPath = null;
        $cleanupZip = false;

        if ($req['source_type'] === self::SOURCE_UPLOAD) {
            $zipPath = ThemePackage::pendingFilePath((string) $req['source_file']);
            if ($zipPath === null) {
                return ['ok' => false, 'errors' => ['Fichier en attente introuvable (expire ou supprime ?).']];
            }
        } else {
            $fetched = ThemePackage::fetchUrl((string) $req['source_url']);
            if (!$fetched['ok']) return $fetched;
            $zipPath = $fetched['path'];
            $cleanupZip = true;
        }

        // Extraction + validation
        $extracted = ThemePackage::extract((string) $zipPath);
        if ($cleanupZip) @unlink($zipPath);
        if (!$extracted['ok']) return $extracted;

        // Installation
        $installed = ThemePackage::install($extracted['path'], $overwrite);
        ThemePackage::removeDirRecursive($extracted['path']);
        if (!$installed['ok']) return $installed;

        // Marque the request comme approuvee
        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE theme_requests
            SET status = 'approved',
                reviewed_by = ?,
                reviewed_at = datetime('now'),
                review_notes = ?,
                installed_theme_id = ?
            WHERE id = ?
        ");
        $stmt->execute([$reviewerId, $notes, $installed['id'], $requestId]);

        // Delete pending uploaded file (it has been installed)
        if ($req['source_type'] === self::SOURCE_UPLOAD && !empty($req['source_file'])) {
            ThemePackage::deletePendingFile((string) $req['source_file']);
        }

        return ['ok' => true, 'theme_id' => $installed['id']];
    }

    /**
     * Rejette a request.
     * @return array{ok:bool,errors?:string[]}
     */
    public static function reject(int $requestId, int $reviewerId, string $notes = ''): array {
        $req = self::getById($requestId);
        if ($req === null) {
            return ['ok' => false, 'errors' => ['Demande introuvable.']];
        }
        if ($req['status'] !== self::STATUS_PENDING) {
            return ['ok' => false, 'errors' => ['Cette demande a deja ete traitee.']];
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE theme_requests
            SET status = 'rejected',
                reviewed_by = ?,
                reviewed_at = datetime('now'),
                review_notes = ?
            WHERE id = ?
        ");
        $stmt->execute([$reviewerId, $notes, $requestId]);

        if ($req['source_type'] === self::SOURCE_UPLOAD && !empty($req['source_file'])) {
            ThemePackage::deletePendingFile((string) $req['source_file']);
        }

        return ['ok' => true];
    }
}
