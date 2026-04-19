<?php

final class SensitiveEntitySecretManager
{
    public const CONTEXT_USER_TOTP = 'user_totp';
    public const CONTEXT_API_WEBHOOK = 'api_webhook';

    private const CONTEXTS = [
        self::CONTEXT_USER_TOTP => [
            'table' => 'users',
            'type' => 'user',
            'name' => 'totp',
            'ref_column' => 'totp_secret_ref',
            'legacy_column' => 'totp_secret',
            'clear_legacy_to' => null,
        ],
        self::CONTEXT_API_WEBHOOK => [
            'table' => 'api_webhooks',
            'type' => 'api-webhook',
            'name' => 'secret',
            'ref_column' => 'secret_ref',
            'legacy_column' => 'secret',
            'clear_legacy_to' => '',
        ],
    ];

    private static ?string $resolvedWritableSource = null;

    public static function hasSecret(string $context, array $entity): bool
    {
        $config = self::config($context);
        $ref = trim((string) ($entity[$config['ref_column']] ?? ''));
        if ($ref !== '' && SecretStore::isSecretRef($ref)) {
            return true;
        }

        return self::legacyValue($config, $entity) !== null;
    }

    public static function storeSecret(string $context, int $entityId, string $value, array $metadata = [], ?array $entity = null): string
    {
        $config = self::config($context);
        if ($entityId < 1) {
            throw new InvalidArgumentException('Identifiant d entite invalide.');
        }

        $db = Database::getInstance();
        $entity = $entity ?? self::loadEntity($config, $entityId);
        $currentRef = trim((string) ($entity[$config['ref_column']] ?? ''));
        $source = self::writableSource();
        $nextRef = ($currentRef !== '' && SecretStore::isSecretRef($currentRef) && SecretStore::providerNameForRef($currentRef) === $source)
            ? $currentRef
            : SecretStore::writableRef($config['type'], $entityId, $config['name'], $source);

        $startedTransaction = !$db->inTransaction();
        if ($startedTransaction) {
            $db->beginTransaction();
        }

        try {
            SecretStore::put($nextRef, $value, $metadata + [
                'entity' => $config['table'],
                'id' => $entityId,
                'context' => $context,
            ]);
            self::persistReference($db, $config, $entityId, $nextRef);
            if ($currentRef !== '' && $currentRef !== $nextRef && SecretStore::isSecretRef($currentRef)) {
                SecretStore::delete($currentRef);
            }
            if ($startedTransaction) {
                $db->commit();
            }
            return $nextRef;
        } catch (Throwable $e) {
            if ($startedTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            if ($nextRef !== $currentRef && SecretStore::isSecretRef($nextRef)) {
                try {
                    SecretStore::delete($nextRef);
                } catch (Throwable $cleanupError) {
                }
            }
            throw $e;
        }
    }

    public static function getSecret(string $context, array $entity, string $purpose = 'runtime', array $accessContext = []): ?string
    {
        $config = self::config($context);
        $entityId = (int) ($entity['id'] ?? 0);
        $ref = trim((string) ($entity[$config['ref_column']] ?? ''));
        if ($ref !== '' && SecretStore::isSecretRef($ref)) {
            $secret = SecretStore::get($ref, $purpose, $accessContext);
            self::promoteStoredSecretIfNeeded($context, $entity, $ref, $secret);
            return $secret;
        }

        $legacy = self::legacyValue($config, $entity);
        if ($legacy === null && $entityId > 0) {
            $entity = self::loadEntity($config, $entityId);
            $ref = trim((string) ($entity[$config['ref_column']] ?? ''));
            if ($ref !== '' && SecretStore::isSecretRef($ref)) {
                $secret = SecretStore::get($ref, $purpose, $accessContext);
                self::promoteStoredSecretIfNeeded($context, $entity, $ref, $secret);
                return $secret;
            }
            $legacy = self::legacyValue($config, $entity);
        }
        if ($legacy === null) {
            return null;
        }

        self::migrateLegacySecretIfNeeded($context, $entity);
        return $legacy;
    }

    public static function migrateLegacySecretIfNeeded(string $context, array $entity): ?string
    {
        $config = self::config($context);
        $entityId = (int) ($entity['id'] ?? 0);
        if ($entityId < 1) {
            return null;
        }

        $ref = trim((string) ($entity[$config['ref_column']] ?? ''));
        if ($ref !== '' && SecretStore::isSecretRef($ref)) {
            return $ref;
        }

        $legacy = self::legacyValue($config, $entity);
        if ($legacy === null) {
            $entity = self::loadEntity($config, $entityId);
            $ref = trim((string) ($entity[$config['ref_column']] ?? ''));
            if ($ref !== '' && SecretStore::isSecretRef($ref)) {
                return $ref;
            }
            $legacy = self::legacyValue($config, $entity);
        }
        if ($legacy === null) {
            return null;
        }

        try {
            return self::storeSecret($context, $entityId, $legacy, [
                'migrated_from' => 'legacy_db_column',
            ], $entity);
        } catch (Throwable $e) {
            self::logMigrationFailure($context, $entityId, $e);
            return null;
        }
    }

    public static function clearSecret(string $context, int $entityId, ?array $entity = null): void
    {
        $config = self::config($context);
        if ($entityId < 1) {
            return;
        }

        $db = Database::getInstance();
        $entity = $entity ?? self::loadEntity($config, $entityId);
        $ref = trim((string) ($entity[$config['ref_column']] ?? ''));
        $sql = 'UPDATE ' . $config['table']
            . ' SET ' . $config['ref_column'] . ' = NULL, ' . $config['legacy_column'] . ' = ? WHERE id = ?';
        $db->prepare($sql)->execute([$config['clear_legacy_to'], $entityId]);

        if ($ref !== '' && SecretStore::isSecretRef($ref)) {
            SecretStore::delete($ref);
        }
    }

    public static function resetRuntimeCache(): void
    {
        self::$resolvedWritableSource = null;
    }

    private static function config(string $context): array
    {
        $config = self::CONTEXTS[$context] ?? null;
        if (!is_array($config)) {
            throw new InvalidArgumentException('Contexte de secret inconnu.');
        }
        return $config;
    }

    private static function writableSource(): string
    {
        if (self::$resolvedWritableSource !== null) {
            return self::$resolvedWritableSource;
        }
        self::$resolvedWritableSource = SecretStore::resolvedWritableSource();
        return self::$resolvedWritableSource;
    }

    private static function promoteStoredSecretIfNeeded(string $context, array $entity, string $ref, ?string $secret): void
    {
        if ($secret === null || $secret === '') {
            return;
        }

        if (SecretStore::providerNameForRef($ref) !== 'local' || self::writableSource() !== 'agent') {
            return;
        }

        $entityId = (int) ($entity['id'] ?? 0);
        if ($entityId < 1) {
            return;
        }

        try {
            $nextRef = self::storeSecret($context, $entityId, $secret, [
                'migrated_from' => 'local_fallback_ref',
            ], $entity);
            if ($nextRef !== $ref) {
                SecretRedaction::errorLog('Fulgurite security info: remigrated secret for ' . $context . ' #' . $entityId . ' back to broker.');
            }
        } catch (Throwable $e) {
            self::logRemigrationFailure($context, $entityId, $e, [$ref, $secret]);
        }
    }

    private static function persistReference(PDO $db, array $config, int $entityId, string $ref): void
    {
        $sql = 'UPDATE ' . $config['table']
            . ' SET ' . $config['ref_column'] . ' = ?, ' . $config['legacy_column'] . ' = ? WHERE id = ?';
        $db->prepare($sql)->execute([$ref, $config['clear_legacy_to'], $entityId]);
    }

    private static function loadEntity(array $config, int $entityId): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT id, ' . $config['ref_column'] . ', ' . $config['legacy_column'] . ' FROM ' . $config['table'] . ' WHERE id = ?'
        );
        $stmt->execute([$entityId]);
        $entity = $stmt->fetch();
        if (!is_array($entity)) {
            throw new RuntimeException('Entite de secret introuvable.');
        }
        return $entity;
    }

    private static function legacyValue(array $config, array $entity): ?string
    {
        $value = $entity[$config['legacy_column']] ?? null;
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        return $value === '' ? null : $value;
    }

    private static function logMigrationFailure(string $context, int $entityId, Throwable $e): void
    {
        SecretRedaction::errorLog('Fulgurite security warning: unable to migrate legacy secret for '
            . $context . ' #' . $entityId . ': ' . SecretRedaction::safeThrowableMessage($e));
    }

    private static function logRemigrationFailure(string $context, int $entityId, Throwable $e, array $explicitValues = []): void
    {
        SecretRedaction::errorLog(
            'Fulgurite security warning: unable to remigrate fallback secret for '
            . $context . ' #' . $entityId . ': ' . SecretRedaction::safeThrowableMessage($e, $explicitValues),
            $explicitValues
        );
    }
}
