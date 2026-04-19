<?php

declare(strict_types=1);

define('FULGURITE_CLI', true);

$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . '/fulgurite-explore-preview-test-' . bin2hex(random_bytes(4));
mkdir($tmp, 0700, true);

putenv('DB_DRIVER=sqlite');
putenv('DB_PATH=' . $tmp . '/fulgurite.db');
putenv('SEARCH_DB_PATH=' . $tmp . '/fulgurite-search.db');
$_ENV['DB_DRIVER'] = 'sqlite';
$_ENV['DB_PATH'] = $tmp . '/fulgurite.db';
$_ENV['SEARCH_DB_PATH'] = $tmp . '/fulgurite-search.db';
$_SERVER['DB_DRIVER'] = 'sqlite';
$_SERVER['DB_PATH'] = $tmp . '/fulgurite.db';
$_SERVER['SEARCH_DB_PATH'] = $tmp . '/fulgurite-search.db';

require_once $root . '/src/bootstrap.php';
require_once $root . '/src/ExploreView.php';

function failTest(string $message): void {
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function assertTrueValue(bool $condition, string $message): void {
    if (!$condition) {
        failTest($message);
    }
}

function assertContains(string $needle, string $haystack, string $message): void {
    if (!str_contains($haystack, $needle)) {
        failTest($message . "\nNeedle: " . $needle . "\n");
    }
}

function assertNotContains(string $needle, string $haystack, string $message): void {
    if (str_contains($haystack, $needle)) {
        failTest($message . "\nUnexpected needle: " . $needle . "\n");
    }
}

function createUser(string $username, string $role, array $permissions = []): int {
    $db = Database::getInstance();
    $db->prepare("
        INSERT INTO users (username, password, role, permissions_json)
        VALUES (?, ?, ?, ?)
    ")->execute([
        $username,
        password_hash('test', PASSWORD_DEFAULT),
        $role,
        json_encode($permissions, JSON_THROW_ON_ERROR),
    ]);

    return (int) $db->lastInsertId();
}

function loginAsUser(int $userId, string $username, string $role): void {
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    Auth::refreshSessionUser();
}

class FakePreviewRestic extends Restic {
    public array $previewCalls = [];

    public function __construct() {
    }

    public function dumpPreview(string $snapshot, string $filepath, int $timeoutSeconds = 8): array {
        $this->previewCalls[] = [
            'snapshot' => $snapshot,
            'filepath' => $filepath,
            'timeout' => $timeoutSeconds,
        ];

        return [
            'success' => true,
            'content' => "sensitive-preview\nline2",
            'error' => null,
            'timed_out' => false,
        ];
    }
}

function renderViewHtml(Restic $restic, string $path): string {
    ob_start();
    renderExploreMainPanel(
        ['id' => 1, 'name' => 'Repo test'],
        $restic,
        'snap-1',
        $path,
        'view',
        [],
        [
            'short_id' => 'snap-1',
            'time' => '2026-01-01T00:00:00Z',
            'hostname' => 'host1',
            'paths' => ['/'],
        ],
        1
    );
    return (string) ob_get_clean();
}

assertTrueValue(isTextFile('.env'), '.env should still be detected as a text file.');
assertTrueValue(isTextFile('certificate.pem'), '.pem should still be detected as a text file.');
assertTrueValue(isTextFile('private.key'), '.key should still be detected as a text file.');
assertTrueValue(!isTextFile('archive.bin'), 'Unknown binary extension should not be treated as text.');

assertTrueValue(isSensitivePreviewFile('.env'), '.env should be denylisted for inline preview.');
assertTrueValue(isSensitivePreviewFile('id_rsa'), 'id_rsa should be denylisted for inline preview.');
assertTrueValue(isSensitivePreviewFile('kubeconfig'), 'kubeconfig should be denylisted for inline preview.');
assertTrueValue(isSensitivePreviewFile('secrets.production'), 'secrets.* should be denylisted for inline preview.');
assertTrueValue(isSensitivePreviewFile('server.pem'), '.pem files should be denylisted for inline preview.');
assertTrueValue(!isSensitivePreviewFile('notes.txt'), 'Regular text files should not be denylisted for inline preview.');

$maskedUserId = createUser('preview-mask-user', ROLE_VIEWER);
loginAsUser($maskedUserId, 'preview-mask-user', ROLE_VIEWER);

$maskedRestic = new FakePreviewRestic();
$maskedHtml = renderViewHtml($maskedRestic, '/root/.env');

assertContains('Apercu inline masque pour ce fichier sensible.', $maskedHtml, 'Sensitive preview should be masked without the dedicated permission.');
assertContains('/api/download_file.php?repo_id=1&snapshot=snap-1&path=%2Froot%2F.env', $maskedHtml, 'Download link should remain available for sensitive files.');
assertNotContains('sensitive-preview', $maskedHtml, 'Masked sensitive preview should not render file contents.');
assertTrueValue($maskedRestic->previewCalls === [], 'Sensitive masked preview should not call restic dumpPreview.');

$allowedUserId = createUser('preview-allow-user', ROLE_VIEWER, ['repos.view_sensitive_files' => true]);
loginAsUser($allowedUserId, 'preview-allow-user', ROLE_VIEWER);

$allowedRestic = new FakePreviewRestic();
$allowedHtml = renderViewHtml($allowedRestic, '/root/secrets.production');

assertContains('Apercu texte du snapshot en lecture seule.', $allowedHtml, 'Dedicated permission should restore inline preview for sensitive files.');
assertContains('sensitive-preview', $allowedHtml, 'Allowed sensitive preview should render file contents.');
assertTrueValue(count($allowedRestic->previewCalls) === 1, 'Allowed sensitive preview should call restic dumpPreview exactly once.');

echo "Explore sensitive preview tests OK.\n";
