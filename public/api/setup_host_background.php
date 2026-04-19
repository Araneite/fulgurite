<?php
// =============================================================================
// setup_host_background.php — Automatic setup of a remote host
// Arguments : $argv[1] = host_id, $argv[2] = log_file, $argv[3] = createds_file
// =============================================================================

set_time_limit(0);
ini_set('max_execution_time', 0);

$hostId    = (int)   ($argv[1] ?? 0);
$logFile   = (string)($argv[2] ?? '/tmp/fulgurite_setup_bg.log');
$credsFile = (string)($argv[3] ?? '');

if (!$hostId) exit(1);

$_SESSION = [];
define('FULGURITE_CLI', true);

require_once __DIR__ . '/../../src/bootstrap.php';
ProcessRunner::daemonizeFromEnvironment();

// ── Helpers ───────────────────────────────────────────────────────────────────

function sLog(string $msg): void {
    global $logFile;
    file_put_contents($logFile, '[' . formatCurrentDisplayDate('H:i:s') . '] ' . $msg . "\n", FILE_APPEND);
}

function sSection(string $title): void {
    sLog('');
    sLog('── ' . $title . ' ' . str_repeat('─', max(0, 55 - mb_strlen($title))));
}

function sshRun(array $sshBase, string $remoteCmd, array $env, string $stdin = ''): array {
    $cmd         = array_merge($sshBase, [$remoteCmd]);
    $result = ProcessRunner::run($cmd, ['env' => $env, 'stdin' => $stdin]);
    $final = SshKnownHosts::finalizeSshResult(
        ['success' => (int) ($result['code'] ?? 1) === 0, 'output' => (string) ($result['output'] ?? ''), 'code' => (int) ($result['code'] ?? 1)],
        (string) ($GLOBALS['host']['hostname'] ?? ''),
        (int) ($GLOBALS['host']['port'] ?? 22),
        'setup_host_background'
    );
    return ['rc' => (int) ($final['code'] ?? 1), 'output' => (string) ($final['output'] ?? ''), 'host_key' => $final['host_key'] ?? null];
}

function sshLog(array $sshBase, string $remoteCmd, array $env, string $indent = '  '): array {
    $r = sshRun($sshBase, $remoteCmd, $env);
    foreach (explode("\n", $r['output']) as $line) {
        if (trim($line) !== '') sLog($indent . trim($line));
    }
    return $r;
}

function sshRunScript(array $sshBase, string $script, array $env): array {
    return sshRun($sshBase, 'sh -s', $env, $script);
}

function sshLogScript(array $sshBase, string $script, array $env, string $indent = '  '): array {
    $r = sshRunScript($sshBase, $script, $env);
    foreach (explode("\n", $r['output']) as $line) {
        if (trim($line) !== '') sLog($indent . trim($line));
    }
    return $r;
}

function shQuote(string $value): string {
    return "'" . str_replace("'", "'\"'\"'", $value) . "'";
}

function sudoScript(string $cmd, string $sudoPass, string $sudoUser, string $hostUser, bool $redirectStderr = true): string {
    $stderr = $redirectStderr ? ' 2>&1' : '';
    if ($sudoUser === $hostUser || !$sudoUser) {
        return 'SUDO_PASS=' . shQuote($sudoPass) . "\n"
             . 'printf "%s\n" "$SUDO_PASS" | sudo -S ' . $cmd . $stderr . "\n";
    }

    $inner = 'read -r SUDO_PASS; printf "%s\n" "$SUDO_PASS" | sudo -S ' . $cmd . $stderr;
    return 'SUDO_PASS=' . shQuote($sudoPass) . "\n"
         . '{ printf "%s\n" "$SUDO_PASS"; printf "%s\n" "$SUDO_PASS"; } | su -c ' . shQuote($inner)
         . ' - ' . shQuote($sudoUser) . $stderr . "\n";
}

function resticPasswordScript(string $repoPassword, string $body): string {
    return 'RPASS_VALUE=' . shQuote(trim($repoPassword)) . "\n"
         . 'umask 077' . "\n"
         . '_RPASS=$(mktemp) || exit 1' . "\n"
         . 'trap \'rm -f "$_RPASS"\' EXIT HUP INT TERM' . "\n"
         . 'chmod 600 "$_RPASS" 2>/dev/null || true' . "\n"
         . 'printf "%s" "$RPASS_VALUE" > "$_RPASS" || exit 1' . "\n"
         . $body . "\n"
         . '_RC=$?' . "\n"
         . 'rm -f "$_RPASS"' . "\n"
         . 'trap - EXIT HUP INT TERM' . "\n"
         . 'exit $_RC' . "\n";
}

// ── Init ──────────────────────────────────────────────────────────────────────

file_put_contents($logFile, '');

$host = HostManager::getById($hostId);
if (!$host) {
    sLog("ERREUR: Hôte #{$hostId} introuvable");
    file_put_contents($logFile . '.done', 'error');
    exit(1);
}

// Load temporary createdentials
$tempSudoUser = '';
$tempSudoPass = '';
if ($credsFile && file_exists($credsFile)) {
    $creds        = json_decode(file_get_contents($credsFile), true) ?? [];
    $tempSudoUser = trim($creds['user'] ?? '');
    $tempSudoPass = trim($creds['pass'] ?? '');
    @unlink($credsFile);
}

// Sudo password: host config takes priority, then temporary createdentials
$sudoPassword = HostManager::getSudoPassword($host) ?: $tempSudoPass;
// Sudo user: temporary createdentials if provided, otherwise the SSH user
$sudoUser = $tempSudoUser ?: $host['user'];

sLog("=== Setup : {$host['name']} ({$host['hostname']}) ===");

try {
    $tmpKey = SshKeyManager::getTemporaryKeyFile((int) ($host['ssh_key_id'] ?? 0));
} catch (Throwable $e) {
    sLog("ERREUR: Clé SSH introuvable ({" . ($host['private_key_file'] ?? '') . "})");
    file_put_contents($logFile . '.done', 'error');
    exit(1);
}

$tmpHome = '/tmp/fulgurite-setup-' . $hostId . '-' . uniqid();
mkdir($tmpHome . '/.ssh', 0700, true);

$sshBase = array_merge([
    SSH_BIN,
    '-i', $tmpKey,
    '-p', (string) $host['port'],
], SshKnownHosts::sshOptions((string) $host['hostname'], (int) $host['port'], 15), [
    $host['user'] . '@' . $host['hostname'],
]);

$env = [
    'HOME' => $tmpHome,
    'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
];

$overallOk = true;
$bkpHost   = AppConfig::backupServerHost();
$bkpUser   = AppConfig::backupServerSftpUser();

// ── Step 1 : connection SSH ───────────────────────────────────────────────────

sSection('Étape 1 : Connexion SSH');

$r = sshRun($sshBase, 'echo SSH_OK && hostname && uname -m', $env);
if ($r['rc'] === 0 && str_contains($r['output'], 'SSH_OK')) {
    $lines = array_filter(explode("\n", $r['output']), fn($l) => trim($l) !== '' && $l !== 'SSH_OK');
    sLog("✓ Connexion établie — " . implode(' / ', array_map('trim', $lines)));
} else {
    sLog("✗ Connexion SSH échouée");
    file_put_contents($logFile . '.done', 'error');
    FileSystem::removeDirectory($tmpHome);
    exit(1);
}

// ── Step 2 : Restic ──────────────────────────────────────────────────────────

sSection('Étape 2 : Restic');

$r = sshRun($sshBase, 'restic version 2>/dev/null || /usr/local/bin/restic version 2>/dev/null', $env);
$resticBin = 'restic';
$resticOk  = $r['rc'] === 0 && str_contains($r['output'], 'restic');

if ($resticOk) {
    sLog("✓ " . trim(explode("\n", $r['output'])[0]));
} else {
    sLog("⚠ Restic non disponible — installation nécessaire");

    if (!$sudoPassword) {
        sLog("✗ Aucun mot de passe sudo disponible — impossible d'installer automatiquement");
        sLog("  Fournissez un mot de passe sudo temporaire dans le formulaire Setup");
        $overallOk = false;
    } else {
        if ($sudoUser !== $host['user']) {
            sLog("  Utilisation du compte sudo : {$sudoUser}");
        }

        // Build apt command depending on same user or different user
        $aptBase = 'DEBIAN_FRONTEND=noninteractive apt-get install -y restic';

        sLog("  → Tentative via apt-get install...");
        $r = sshLogScript($sshBase, sudoScript($aptBase, $sudoPassword, $sudoUser, $host['user']), $env);

        if ($r['rc'] === 0) {
            $rv = sshRun($sshBase, 'restic version 2>&1', $env);
            sLog("✓ Restic installé via apt — " . trim(explode("\n", $rv['output'])[0]));
            $resticOk = true;
        } else {
            sLog("  → apt-get échoué — téléchargement du binaire officiel (v0.17.3)...");

            $dlScript = <<<'BASH'
set -e
ARCH=$(dpkg --print-architecture 2>/dev/null || uname -m)
case "$ARCH" in
  amd64|x86_64)   RARCH=amd64  ;;
  arm64|aarch64)  RARCH=arm64  ;;
  armhf|armv7l)   RARCH=arm    ;;
  i386|i686)      RARCH=386    ;;
  *)              RARCH=amd64  ;;
esac
VER=0.17.3
URL="https://github.com/restic/restic/releases/download/v${VER}/restic_${VER}_linux_${RARCH}.bz2"
echo "Téléchargement : restic v${VER} (${RARCH})"
if command -v curl >/dev/null 2>&1; then
  curl -fsSL "$URL" | bunzip2 > /tmp/restic_dl
elif command -v wget >/dev/null 2>&1; then
  wget -qO- "$URL" | bunzip2 > /tmp/restic_dl
else
  echo "ERREUR: curl et wget sont absents" >&2; exit 1
fi
chmod +x /tmp/restic_dl
BASH;
            $mvBase = 'mv /tmp/restic_dl /usr/local/bin/restic';
            $dlScript .= sudoScript($mvBase, $sudoPassword, $sudoUser, $host['user']);
            $dlScript .= 'restic version 2>&1';

            $r = sshLogScript($sshBase, $dlScript, $env);
            if ($r['rc'] === 0) {
                sLog("✓ Restic installé via binaire officiel");
                $resticBin = '/usr/local/bin/restic';
                $resticOk  = true;
            } else {
                sLog("✗ Installation de restic échouée");
                $overallOk = false;
            }
        }
    }
}

// ── Step 3: SSH key for SFTP return ───────────────────────────────────────────

sSection('Étape 3 : Clé SSH pour le retour SFTP');

if (!$bkpHost) {
    sLog("⚠ Hote SFTP de backup non configure — etape ignoree");
} else {
    // Retrieve remote host public key
    $r = sshRun($sshBase,
        'cat ~/.ssh/id_ed25519.pub 2>/dev/null || cat ~/.ssh/id_rsa.pub 2>/dev/null',
        $env
    );
    $pubKey = trim($r['output']);

    if (!$pubKey || !str_starts_with($pubKey, 'ssh-')) {
        sLog("  Aucune clé publique trouvée — génération automatique...");

        // Generate key directly (logged in as SSH user)
        $genCmd = 'mkdir -p ~/.ssh && chmod 700 ~/.ssh'
                . ' && ssh-keygen -t ed25519 -N "" -f ~/.ssh/id_ed25519 2>&1'
                . ' && cat ~/.ssh/id_ed25519.pub';
        $rg = sshRun($sshBase, $genCmd, $env);

        if ($rg['rc'] === 0) {
            // Extract public key from output
            foreach (explode("\n", $rg['output']) as $line) {
                if (str_starts_with(trim($line), 'ssh-')) {
                    $pubKey = trim($line);
                    break;
                }
            }
            if ($pubKey) {
                sLog("✓ Clé SSH générée pour {$host['user']}@{$host['hostname']}");
            } else {
                sLog("✗ Génération de clé échouée");
                sLog("  → Créez manuellement : ssh-keygen -t ed25519 -N '' -f ~/.ssh/id_ed25519");
                $overallOk = false;
            }
        } else {
            sLog("✗ Génération de clé échouée");
            foreach (explode("\n", $rg['output']) as $line) {
                if (trim($line)) sLog("  " . trim($line));
            }
            $overallOk = false;
        }
    }

    if ($pubKey && str_starts_with($pubKey, 'ssh-')) {
        $keyParts    = explode(' ', $pubKey);
        $keyMaterial = $keyParts[1] ?? '';
        sLog("  Clé publique récupérée : " . substr($pubKey, 0, 40) . "...");

        // Check if key is already in authorized_keys
        $akFile   = '/home/' . $bkpUser . '/.ssh/authorized_keys';
        $akDir    = '/home/' . $bkpUser . '/.ssh';
        $existing = @file_get_contents($akFile) ?: '';

        if ($keyMaterial && str_contains($existing, $keyMaterial)) {
            sLog("✓ Clé déjà présente dans {$akFile}");
        } else {
            // Ensure .ssh directory exists on this server
            if (!is_dir($akDir)) {
                @mkdir($akDir, 0700, true);
            }

            // Attempt 1: direct PHP write
            $written = false;
            if (is_writable($akFile)) {
                $written = file_put_contents($akFile, "\n" . $pubKey . "\n", FILE_APPEND) !== false;
            } elseif (is_dir($akDir) && is_writable($akDir)) {
                $written = file_put_contents($akFile, $pubKey . "\n", FILE_APPEND) !== false;
                if ($written) chmod($akFile, 0600);
            }

            // Post-write verification (tee can succeed on stdout even if file is inaccessible)
            if ($written) {
                $check = @file_get_contents($akFile) ?: '';
                if (!str_contains($check, $keyMaterial)) {
                    $written = false; // false positive
                }
            }

            // Attempt 2: sudo tee if php-fpm runs with sudo configured
            if (!$written) {
                ProcessRunner::run(['sudo', 'tee', '-a', $akFile], [
                    'stdin' => $pubKey . "\n",
                    'validate_binary' => false,
                ]);
                $check   = @file_get_contents($akFile) ?: '';
                $written = $keyMaterial && str_contains($check, $keyMaterial);
            }

            if ($written) {
                sLog("✓ Clé ajoutée dans {$akFile}");
            } else {
                sLog("⚠ Ajout automatique impossible (permissions)");
                sLog("  Exécutez sur le serveur backup :");
                sLog("  mkdir -p {$akDir} && chmod 700 {$akDir}");
                sLog("  echo " . escapeshellarg($pubKey) . " >> {$akFile}");
                sLog("  chmod 600 {$akFile}");
                // Non-blocking for overallOk
            }
        }
    }
}

// ── Step 4: SFTP return ───────────────────────────────────────────────────────

sSection('Étape 4 : Test retour SFTP');

if (!$bkpHost) {
    sLog("⚠ Hote SFTP de backup non configure — etape ignoree");
} else {
    sLog("  Test : {$bkpUser}@{$bkpHost}");
    $knownHostTarget = escapeshellarg($bkpHost);
    $sftpTest = 'mkdir -p ~/.ssh && chmod 700 ~/.ssh'
              . ' && touch ~/.ssh/known_hosts && chmod 600 ~/.ssh/known_hosts'
              . ' && ssh -o BatchMode=yes -o StrictHostKeyChecking=yes'
              . ' -o UserKnownHostsFile=~/.ssh/known_hosts -o GlobalKnownHostsFile=/dev/null -o ConnectTimeout=10 '
              . escapeshellarg($bkpUser . '@' . $bkpHost)
              . " 'echo SFTP_OK' 2>&1";
    $r = sshRun($sshBase, $sftpTest, $env);

    if ($r['rc'] === 0 && str_contains($r['output'], 'SFTP_OK')) {
        sLog("✓ Retour SFTP opérationnel ({$bkpUser}@{$bkpHost})");
    } else {
        sLog("✗ Impossible d'atteindre {$bkpUser}@{$bkpHost}");
        foreach (explode("\n", $r['output']) as $line) {
            if (trim($line)) sLog("  " . trim($line));
        }
        $overallOk = false;
    }
}

// ── Step 5 : Test sudo ───────────────────────────────────────────────────────

if ($sudoPassword) {
    sSection('Étape 5 : Test sudo');

    if ($sudoUser !== $host['user'] && $sudoUser) {
        sLog("  Compte sudo : {$sudoUser} (différent de {$host['user']})");
        $testCmd = sudoScript('true', $sudoPassword, $sudoUser, $host['user'])
                 . '_RC=$?' . "\n"
                 . 'if [ "$_RC" -eq 0 ]; then echo SUDO_OK; fi' . "\n"
                 . 'exit "$_RC"' . "\n";
    } else {
        $testCmd = sudoScript('true', $sudoPassword, $sudoUser, $host['user'], false)
                 . '_RC=$?' . "\n"
                 . 'if [ "$_RC" -eq 0 ]; then echo SUDO_OK; fi' . "\n"
                 . 'exit "$_RC"' . "\n";
    }

    $r = sshRunScript($sshBase, $testCmd, $env);
    if ($r['rc'] === 0 && str_contains($r['output'], 'SUDO_OK')) {
        sLog("✓ Sudo fonctionnel ({$sudoUser})");
    } else {
        sLog("✗ Sudo échoué pour {$sudoUser}");
        foreach (explode("\n", $r['output']) as $line) {
            if (trim($line)) sLog("  " . trim($line));
        }
        $overallOk = false;
    }
}

// ── Step 6 : repositories ──────────────────────────────────────────────────────────

sSection('Étape 6 : Vérification / Initialisation des dépôts');

if (!$resticOk) {
    sLog("⚠ Restic non disponible — vérification des dépôts ignorée");
} else {
    $db   = Database::getInstance();
    $stmt = $db->prepare("
        SELECT bj.id, bj.name, bj.repo_id, bj.remote_repo_path,
               r.path AS repo_path, r.name AS repo_name
        FROM backup_jobs bj
        JOIN repos r ON r.id = bj.repo_id
        WHERE bj.host_id = ?
    ");
    $stmt->execute([$hostId]);
    $jobs = $stmt->fetchAll();

    if (empty($jobs)) {
        sLog("  Aucun job associé à cet hôte — aucun dépôt à vérifier");
    } else {
        sLog("  " . count($jobs) . " job(s) trouvé(s)");

        foreach ($jobs as $job) {
            $remoteRepoPath = !empty($job['remote_repo_path'])
                ? $job['remote_repo_path']
                : $job['repo_path'];

            sLog('');
            sLog("  Job : {$job['name']} → {$remoteRepoPath}");

            $repo = RepoManager::getById((int) $job['repo_id']);
            if (!$repo) { sLog("  ✗ Dépôt introuvable en base"); continue; }

            $repoPassword = RepoManager::getPassword($repo);
            if ($repoPassword === '') { sLog("  ✗ Mot de passe du dépôt introuvable"); continue; }

            $checkCmd = resticPasswordScript($repoPassword,
                $resticBin . ' -r ' . escapeshellarg($remoteRepoPath)
                . ' --password-file "$_RPASS" --cache-dir /tmp/restic-cache'
                . ' cat config > /dev/null 2>&1'
            );

            $r = sshRunScript($sshBase, $checkCmd, $env);

            if ($r['rc'] === 0) {
                sLog("  ✓ Dépôt déjà initialisé");
            } else {
                sLog("  ⚠ Dépôt non initialisé — lancement de restic init...");
                $initCmd = resticPasswordScript($repoPassword,
                    $resticBin . ' -r ' . escapeshellarg($remoteRepoPath)
                    . ' --password-file "$_RPASS" --cache-dir /tmp/restic-cache'
                    . ' init 2>&1'
                );

                $ri = sshLogScript($sshBase, $initCmd, $env, '    ');
                if ($ri['rc'] === 0) {
                    sLog("  ✓ Dépôt initialisé avec succès");
                } else {
                    sLog("  ✗ Initialisation échouée");
                    $overallOk = false;
                }
            }
        }
    }
}

// ── Final result ────────────────────────────────────────────────────────────

sLog('');
sLog(str_repeat('─', 60));
sLog($overallOk ? "✓ Setup terminé avec succès" : "⚠ Setup terminé avec des avertissements");

@unlink($tmpKey);
FileSystem::removeDirectory($tmpHome);
file_put_contents($logFile . '.done', $overallOk ? 'success' : 'warning');
