<?php
declare(strict_types=1);

final class FilesystemScopeConfigWriter
{
    public static function buildConfigPhp(array $policy): string
    {
        $payload = [
            'version' => 1,
            'scopes' => array_values((array) ($policy['scopes'] ?? [])),
        ];

        return "<?php\nreturn " . var_export($payload, true) . ";\n";
    }

    public static function writeConfigPhp(string $path, array $policy): bool
    {
        return @file_put_contents($path, self::buildConfigPhp($policy), LOCK_EX) !== false;
    }
}
