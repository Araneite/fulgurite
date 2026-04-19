<?php
define('FULGURITE_CLI', true);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/src/Setup/SetupGuard.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Ce script doit etre execute en CLI.\n");
    exit(1);
}

$argv = $_SERVER['argv'] ?? [];
$command = strtolower((string) ($argv[1] ?? 'help'));

function setupBootstrapUsage(): void
{
    echo "Usage:\n";
    echo "  php scripts/setup-bootstrap.php create [--ttl=30]\n";
    echo "  php scripts/setup-bootstrap.php status\n";
    echo "  php scripts/setup-bootstrap.php clear\n";
}

function setupBootstrapReadTtlMinutes(array $args): int
{
    foreach ($args as $arg) {
        if (preg_match('/^--ttl=(\d+)$/', (string) $arg, $matches) === 1) {
            return max(5, (int) $matches[1]);
        }
    }
    return 30;
}

switch ($command) {
    case 'create':
        $ttlMinutes = setupBootstrapReadTtlMinutes(array_slice($argv, 2));
        $result = SetupGuard::createBootstrapToken($ttlMinutes * 60);
        if (empty($result['ok'])) {
            fwrite(STDERR, ($result['message'] ?? 'Impossible de generer le token bootstrap.') . "\n");
            exit(1);
        }

        echo "Token bootstrap genere.\n";
        echo "Token      : " . $result['token'] . "\n";
        echo "Expire le  : " . date('Y-m-d H:i:s', (int) $result['expires_at']) . "\n";
        echo "Fichier    : " . $result['path'] . "\n";
        echo "Session web: " . SetupGuard::sessionTtlSeconds() . " secondes glissantes apres autorisation.\n";
        exit(0);

    case 'status':
        $status = SetupGuard::bootstrapStatus();
        echo "Installe   : " . ($status['installed'] ? 'oui' : 'non') . "\n";
        echo "Configure  : " . ($status['configured'] ? 'oui' : 'non') . "\n";
        echo "Utilise    : " . (!empty($status['used']) ? 'oui' : 'non') . "\n";
        echo "Expire     : " . (!empty($status['expired']) ? 'oui' : 'non') . "\n";
        echo "Fichier    : " . ($status['path'] ?? SetupGuard::bootstrapFile()) . "\n";
        if (!empty($status['created_at'])) {
            echo "Cree le    : " . date('Y-m-d H:i:s', (int) $status['created_at']) . "\n";
        }
        if (!empty($status['expires_at'])) {
            echo "Expire le  : " . date('Y-m-d H:i:s', (int) $status['expires_at']) . "\n";
        }
        if (!empty($status['used_at'])) {
            echo "Consomme le: " . date('Y-m-d H:i:s', (int) $status['used_at']) . "\n";
        }
        if (!empty($status['used_by'])) {
            echo "Consomme par: " . $status['used_by'] . "\n";
        }
        exit(0);

    case 'clear':
        if (!SetupGuard::clearBootstrapToken()) {
            fwrite(STDERR, "Impossible de supprimer le token bootstrap.\n");
            exit(1);
        }
        echo "Token bootstrap supprime et session setup invalidee.\n";
        exit(0);

    case 'help':
    default:
        setupBootstrapUsage();
        exit($command === 'help' ? 0 : 1);
}
