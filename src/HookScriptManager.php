<?php

class HookScriptManager {
    public static function scriptsDir(): string {
        $dir = dirname(DB_PATH) . '/script_catalog';
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        @chmod($dir, 0700);
        return $dir;
    }

    public static function getAll(): array {
        $db = Database::getInstance();
        return $db->query("
            SELECT s.*,
                   uc.username AS created_by_username,
                   uu.username AS updated_by_username
            FROM hook_scripts s
            LEFT JOIN users uc ON uc.id = s.created_by
            LEFT JOIN users uu ON uu.id = s.updated_by
            ORDER BY s.name ASC
        ")->fetchAll();
    }

    public static function getSelectable(?string $jobMode = null): array {
        $scripts = array_values(array_filter(self::getAll(), static function (array $script) use ($jobMode): bool {
            if (($script['status'] ?? 'active') !== 'active') {
                return false;
            }
            if ($jobMode === null) {
                return true;
            }
            $scope = (string) ($script['execution_scope'] ?? 'both');
            return $scope === 'both' || $scope === $jobMode;
        }));

        return $scripts;
    }

    public static function getById(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT s.*,
                   uc.username AS created_by_username,
                   uu.username AS updated_by_username
            FROM hook_scripts s
            LEFT JOIN users uc ON uc.id = s.created_by
            LEFT JOIN users uu ON uu.id = s.updated_by
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function getContent(array $script): string {
        $path = (string) ($script['content_path'] ?? '');
        if ($path === '' || !is_file($path)) {
            throw new RuntimeException('Fichier de script introuvable.');
        }
        $content = (string) file_get_contents($path);
        $checksum = hash('sha256', HookScriptSecurity::normalizeContent($content));
        if ($checksum !== (string) ($script['checksum'] ?? '')) {
            throw new RuntimeException('Le contenu du script ne correspond plus au hash approuve.');
        }
        return $content;
    }

    public static function create(string $name, string $description, string $scope, string $content, int $userId): array {
        $validated = HookScriptSecurity::validate($name, $content, $scope);
        if (!$validated['ok']) {
            return $validated;
        }

        $db = Database::getInstance();
        $path = self::buildContentPath($validated['name']);
        self::writeContent($path, $validated['content']);

        $db->prepare("
            INSERT INTO hook_scripts (
                name, description, execution_scope, status, content_path,
                checksum, instruction_count, created_by, updated_by, updated_at
            ) VALUES (?, ?, ?, 'active', ?, ?, ?, ?, ?, " . Database::nowExpression() . ")
        ")->execute([
            $validated['name'],
            trim($description),
            $validated['scope'],
            $path,
            $validated['checksum'],
            (int) $validated['line_count'],
            $userId > 0 ? $userId : null,
            $userId > 0 ? $userId : null,
        ]);

        return ['ok' => true, 'id' => (int) $db->lastInsertId()];
    }

    public static function update(int $id, string $name, string $description, string $scope, string $content, int $userId): array {
        $script = self::getById($id);
        if (!$script) {
            return ['ok' => false, 'errors' => ['Script introuvable.']];
        }

        $validated = HookScriptSecurity::validate($name, $content, $scope);
        if (!$validated['ok']) {
            return $validated;
        }

        $path = (string) ($script['content_path'] ?? '');
        if ($path === '') {
            $path = self::buildContentPath($validated['name']);
        }
        self::writeContent($path, $validated['content']);

        Database::getInstance()->prepare("
            UPDATE hook_scripts
            SET name = ?, description = ?, execution_scope = ?, content_path = ?,
                checksum = ?, instruction_count = ?, updated_by = ?, updated_at = " . Database::nowExpression() . "
            WHERE id = ?
        ")->execute([
            $validated['name'],
            trim($description),
            $validated['scope'],
            $path,
            $validated['checksum'],
            (int) $validated['line_count'],
            $userId > 0 ? $userId : null,
            $id,
        ]);

        return ['ok' => true, 'id' => $id];
    }

    public static function setStatus(int $id, string $status, int $userId): bool {
        if (!in_array($status, ['active', 'disabled'], true)) {
            return false;
        }
        return Database::getInstance()->prepare("
            UPDATE hook_scripts
            SET status = ?, updated_by = ?, updated_at = " . Database::nowExpression() . "
            WHERE id = ?
        ")->execute([$status, $userId > 0 ? $userId : null, $id]);
    }

    public static function scopeLabel(string $scope): string {
        return match ($scope) {
            'local' => 'Local',
            'remote' => 'Distant',
            default => 'Local + distant',
        };
    }

    private static function buildContentPath(string $name): string {
        $slug = preg_replace('/[^a-z0-9_-]+/i', '_', strtolower($name)) ?: 'script';
        return self::scriptsDir() . '/' . $slug . '_' . bin2hex(random_bytes(6)) . '.txt';
    }

    private static function writeContent(string $path, string $content): void {
        file_put_contents($path, $content);
        @chmod($path, 0600);
    }
}
