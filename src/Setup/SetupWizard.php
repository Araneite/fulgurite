<?php
require_once __DIR__ . '/SetupGuard.php';
// =============================================================================
// SetupWizard.php — Logique of installation of Fulgurite
// =============================================================================

class SetupWizard
{
    // ── Constantes ─────────────────────────────────────────────────────────────

    private const REQUIRED_PHP_VERSION = '8.2.0';

    private const SYSTEM_BINARIES = [
        'restic' => [
            'label'           => 'Binaire restic',
            'config_constant' => 'RESTIC_BIN',
            'command_name'    => 'restic',
            'fallbacks'       => ['/usr/local/bin/restic', '/bin/restic'],
            'fatal'           => true,
        ],
        'ssh' => [
            'label'           => 'Binaire SSH',
            'config_constant' => 'SSH_BIN',
            'command_name'    => 'ssh',
            'fallbacks'       => ['/usr/local/bin/ssh', '/bin/ssh'],
            'fatal'           => false,
        ],
        'rsync' => [
            'label'           => 'Binaire rsync',
            'config_constant' => 'RSYNC_BIN',
            'command_name'    => 'rsync',
            'fallbacks'       => ['/usr/local/bin/rsync', '/bin/rsync'],
            'fatal'           => false,
        ],
        'php_cli' => [
            'label'        => 'PHP CLI',
            'command_name' => 'php',
            'fallbacks'    => ['/usr/local/bin/php', '/usr/bin/php', '/bin/php'],
            'fatal'        => false,
        ],
    ];

    private const REQUIRED_EXTENSIONS = [
        'pdo'      => 'PDO (base de données)',
        'openssl'  => 'OpenSSL (chiffrement)',
        'json'     => 'JSON',
        'mbstring' => 'Multibyte String',
        'session'  => 'Sessions PHP',
        'curl'     => 'cURL (notifications)',
    ];

    private const OPTIONAL_EXTENSIONS = [
        'pdo_sqlite' => 'PDO SQLite',
        'pdo_mysql'  => 'PDO MySQL / MariaDB',
        'pdo_pgsql'  => 'PDO PostgreSQL',
        'gd'         => 'GD (images)',
        'zip'        => 'Zip (thèmes)',
    ];

    // ── Step 1: Prerequisites ───────────────────────────────────────────────

    public static function checkPrerequisites(): array
    {
        $checks = [];
        $allOk  = true;
        $platform = self::detectInstallPlatform();
        $binaryStatuses = [];
        $missing = [
            'required_binaries'   => [],
            'optional_binaries'   => [],
            'required_extensions' => [],
            'optional_extensions' => [],
            'required_fixes'      => [],
            'optional_fixes'      => [],
        ];

        // Version PHP
        $phpOk = version_compare(PHP_VERSION, self::REQUIRED_PHP_VERSION, '>=');
        $checks[] = [
            'label'  => 'PHP ' . self::REQUIRED_PHP_VERSION . '+',
            'value'  => PHP_VERSION,
            'ok'     => $phpOk,
            'fatal'  => true,
        ];
        if (!$phpOk) $allOk = false;

        // Extensions requises
        foreach (self::REQUIRED_EXTENSIONS as $ext => $label) {
            $ok = extension_loaded($ext);
            $checks[] = [
                'label' => 'Extension ' . $label,
                'value' => $ok ? 'Disponible' : 'Manquante',
                'ok'    => $ok,
                'fatal' => true,
            ];
            if (!$ok) {
                $allOk = false;
                $missing['required_extensions'][] = [
                    'key'   => $ext,
                    'label' => 'Extension ' . $label,
                ];
            }
        }

        // Extensions optionnelles
        foreach (self::OPTIONAL_EXTENSIONS as $ext => $label) {
            $ok = extension_loaded($ext);
            $checks[] = [
                'label'    => 'Extension ' . $label,
                'value'    => $ok ? 'Disponible' : 'Non disponible',
                'ok'       => $ok,
                'fatal'    => false,
                'optional' => true,
            ];
            if (!$ok) {
                $missing['optional_extensions'][] = [
                    'key'   => $ext,
                    'label' => 'Extension ' . $label,
                ];
            }
        }

        // Directories
        foreach (self::SYSTEM_BINARIES as $key => $meta) {
            $status = self::inspectBinary($meta);
            $binaryStatuses[$key] = $status;
            $checks[] = [
                'label'    => $meta['label'],
                'value'    => $status['value'],
                'ok'       => $status['ok'],
                'fatal'    => $meta['fatal'],
                'optional' => !$meta['fatal'],
            ];

            if (!$status['ok']) {
                if ($meta['fatal']) {
                    $allOk = false;
                }

                if ($status['fix'] !== null) {
                    $bucket = $meta['fatal'] ? 'required_fixes' : 'optional_fixes';
                    $missing[$bucket][] = $status['fix'];
                } else {
                    $bucket = $meta['fatal'] ? 'required_binaries' : 'optional_binaries';
                    $missing[$bucket][] = [
                        'key'   => $key,
                        'label' => $meta['label'],
                    ];
                }
            }
        }

        $rootDir  = dirname(__DIR__, 2);
        $dataDir  = $rootDir . '/data';
        $configDir = $rootDir . '/config';

        $dirs = [
            $dataDir                     => 'data/ (base de données)',
            $dataDir . '/passwords'      => 'data/passwords/ (mots de passe restic)',
            $dataDir . '/ssh_keys'       => 'data/ssh_keys/ (clés SSH)',
            $dataDir . '/cache'          => 'data/cache/',
            $dataDir . '/themes'         => 'data/themes/ (thèmes)',
        ];

        foreach ($dirs as $dir => $label) {
            if (!is_dir($dir)) {
                $canCreate = is_writable(dirname($dir));
                $checks[] = [
                    'label' => 'Répertoire ' . $label,
                    'value' => $canCreate ? 'Sera créé automatiquement' : 'Introuvable — créez-le manuellement',
                    'ok'    => $canCreate,
                    'fatal' => true,
                ];
                if (!$canCreate) $allOk = false;
            } else {
                $writable = is_writable($dir);
                $checks[] = [
                    'label' => 'Répertoire ' . $label,
                    'value' => $writable ? 'Accessible en écriture' : 'Lecture seule — vérifiez les permissions',
                    'ok'    => $writable,
                    'fatal' => true,
                ];
                if (!$writable) $allOk = false;
            }
        }

        //.env stays outside repository and carries local secrets.
        $configWritable = is_writable($rootDir);
        $checks[] = [
            'label' => 'Répertoire config/ (écriture)',
            'value' => $configWritable ? 'Accessible en écriture' : 'Lecture seule — la configuration devra être faite manuellement',
            'ok'    => $configWritable,
            'fatal' => false,
        ];

        // ── Broker of secrets ────────────────────────────────────────────────
        $brokerCheck = self::checkBrokerEndpoints();
        $checks[] = $brokerCheck;

        return [
            'ok'       => $allOk,
            'checks'   => $checks,
            'commands' => self::buildInstallCommands($missing, $platform),
            'platform' => $platform,
            'rsync'    => self::buildRsyncSetupStatus($binaryStatuses['rsync'] ?? null, $platform),
            'broker'   => $brokerCheck,
        ];
    }

    // ── Step 2: Database ───────────────────────────────────────────────────

    private static function inspectBinary(array $meta): array
    {
        $expectedPath = null;
        if (!empty($meta['config_constant']) && defined($meta['config_constant'])) {
            $expectedPath = (string) constant($meta['config_constant']);
        }

        $search = [];
        if ($expectedPath !== null && $expectedPath !== '') {
            $search[] = $expectedPath;
        }
        foreach (($meta['fallbacks'] ?? []) as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                $search[] = trim($candidate);
            }
        }
        if (!empty($meta['command_name'])) {
            $search[] = (string) $meta['command_name'];
        }

        $foundPath = self::findExecutable($search);

        if ($expectedPath !== null && $expectedPath !== '') {
            if (is_file($expectedPath) && !is_executable($expectedPath)) {
                return [
                    'ok'    => false,
                    'value' => 'Present mais non executable : ' . $expectedPath,
                    'fix'   => [
                        'label'       => $meta['label'],
                        'command'     => 'sudo chmod +x ' . escapeshellarg($expectedPath),
                        'description' => 'Rendre executable le chemin configure par Fulgurite.',
                    ],
                ];
            }

            if (self::isExecutablePath($expectedPath)) {
                return [
                    'ok'    => true,
                    'value' => 'Disponible : ' . $expectedPath,
                    'fix'   => null,
                ];
            }

            if ($foundPath !== null) {
                return [
                    'ok'    => false,
                    'value' => 'Trouve a ' . $foundPath . ' mais Fulgurite attend ' . $expectedPath,
                    'fix'   => [
                        'label'       => $meta['label'],
                        'command'     => 'sudo ln -sf ' . escapeshellarg($foundPath) . ' ' . escapeshellarg($expectedPath),
                        'description' => 'Creer un lien symbolique vers le chemin attendu par Fulgurite.',
                    ],
                ];
            }

            return [
                'ok'    => false,
                'value' => 'Manquant : ' . $expectedPath,
                'fix'   => null,
            ];
        }

        if ($foundPath !== null) {
            return [
                'ok'    => true,
                'value' => 'Disponible : ' . $foundPath,
                'fix'   => null,
            ];
        }

        return [
            'ok'    => false,
            'value' => 'Manquant',
            'fix'   => null,
        ];
    }

    private static function buildInstallCommands(array $missing, array $platform): array
    {
        $commands = [];

        $requiredFixes = self::buildFixCommandGroup(
            'Corriger les chemins obligatoires',
            $missing['required_fixes'],
            true
        );
        if ($requiredFixes !== null) {
            $commands[] = $requiredFixes;
        }

        $optionalFixes = self::buildFixCommandGroup(
            'Corriger les chemins recommandes',
            $missing['optional_fixes'],
            false
        );
        if ($optionalFixes !== null) {
            $commands[] = $optionalFixes;
        }

        $requiredPackages = self::collectPackages(
            array_merge($missing['required_binaries'], $missing['required_extensions']),
            $platform
        );
        if (!empty($requiredPackages['packages'])) {
            $commands[] = [
                'title'       => 'Installer les dependances obligatoires',
                'description' => self::buildCommandDescription(
                    $requiredPackages['labels'],
                    $platform,
                    'Bloquant pour continuer le wizard.'
                ),
                'command'     => self::buildPackageInstallCommand($requiredPackages['packages'], $platform),
            ];
        }

        $optionalPackages = self::collectPackages(
            array_merge($missing['optional_binaries'], $missing['optional_extensions']),
            $platform
        );
        if (!empty($optionalPackages['packages'])) {
            $commands[] = [
                'title'       => 'Installer les dependances recommandees',
                'description' => self::buildCommandDescription(
                    $optionalPackages['labels'],
                    $platform,
                    'Recommande pour profiter de toutes les fonctions.'
                ),
                'command'     => self::buildPackageInstallCommand($optionalPackages['packages'], $platform),
            ];
        }

        return $commands;
    }

    private static function buildRsyncSetupStatus(?array $status, array $platform): array
    {
        $installed = (bool) ($status['ok'] ?? false);
        $package = self::resolvePackageName('rsync', $platform);
        $installCommand = $package !== null && $package !== ''
            ? self::buildPackageInstallCommand([$package], $platform)
            : '';

        return [
            'installed'         => $installed,
            'label'             => self::SYSTEM_BINARIES['rsync']['label'],
            'status_value'      => (string) ($status['value'] ?? ($installed ? 'Disponible' : 'Manquant')),
            'restore_warning'   => 'Sans rsync, le restore ne pourra pas fonctionner.',
            'can_auto_install'  => $installCommand !== '' && self::getPackageManagerBinary($platform) !== '',
            'install_command'   => $installCommand,
            'platform_label'    => (string) ($platform['pretty_name'] ?? ''),
        ];
    }

    /**
     * Check whether the secret broker endpoint(s) are reachable.
     * Non-fatal: a missing broker is acceptable when FULGURITE_SECRET_PROVIDER=local.
     *
     * @return array{label:string,value:string,ok:bool,fatal:bool,optional:true,endpoints:list<array>,mode:string}
     */
    private static function checkBrokerEndpoints(): array
    {
        $provider = getenv('FULGURITE_SECRET_PROVIDER') ?: '';
        if ($provider === 'local') {
            return [
                'label'     => 'Broker de secrets',
                'value'     => 'Mode local actif (FULGURITE_SECRET_PROVIDER=local) — broker HA desactive.',
                'ok'        => true,
                'fatal'     => false,
                'optional'  => true,
                'endpoints' => [],
                'mode'      => 'local',
            ];
        }

        if (!class_exists('SecretStore', false)) {
            return [
                'label'     => 'Broker de secrets',
                'value'     => 'SecretStore non charge — verification impossible pendant le setup.',
                'ok'        => true,
                'fatal'     => false,
                'optional'  => true,
                'endpoints' => [],
                'mode'      => 'unknown',
            ];
        }

        $endpoints = SecretStore::agentEndpoints();
        if (empty($endpoints)) {
            return [
                'label'     => 'Broker de secrets',
                'value'     => 'Aucun endpoint broker configure (FULGURITE_SECRET_BROKER_ENDPOINTS vide).',
                'ok'        => false,
                'fatal'     => false,
                'optional'  => true,
                'endpoints' => [],
                'mode'      => 'ha',
            ];
        }

        // Probe each endpoint.
        $healthyCount = 0;
        $endpointResults = [];
        foreach ($endpoints as $ep) {
            $uri = (string) ($ep['uri'] ?? '');
            $address = ($ep['type'] ?? '') === 'unix'
                ? 'unix://'. ($ep['path'] ?? '')
                : 'tcp://'. ($ep['host'] ?? ''). ':'. ($ep['port'] ?? 0);
            $errno = 0;
            $errstr = '';
            $stream = @stream_socket_client($address, $errno, $errstr, 2.0, STREAM_CLIENT_CONNECT);
            if (is_resource($stream)) {
                @fclose($stream);
                $healthyCount++;
                $endpointResults[] = ['uri' => $uri, 'ok' => true];
            } else {
                $endpointResults[] = ['uri' => $uri, 'ok' => false, 'error' => $errstr];
            }
        }

        $total = count($endpoints);
        $mode  = $total === 1 ? 'single' : 'ha';
        if ($healthyCount === $total) {
            $value = "{$healthyCount}/{$total} endpoint(s) broker accessible(s).";
            $ok    = true;
        } elseif ($healthyCount > 0) {
            $value = "Cluster broker degrade : {$healthyCount}/{$total} endpoint(s) accessible(s). Certains noeuds sont hors-ligne.";
            $ok    = false;
        } else {
            $value = "Aucun endpoint broker accessible ({$total} configure(s)). Demarrez le service fulgurite-secret-agent ou configurez FULGURITE_SECRET_PROVIDER=local.";
            $ok    = false;
        }

        return [
            'label'     => 'Broker de secrets',
            'value'     => $value,
            'ok'        => $ok,
            'fatal'     => false,
            'optional'  => true,
            'endpoints' => $endpointResults,
            'mode'      => $mode,
        ];
    }

    private static function buildFixCommandGroup(string $title, array $fixes, bool $blocking): ?array
    {
        if (empty($fixes)) {
            return null;
        }

        $labels = [];
        $descriptions = [];
        $lines = [];

        foreach ($fixes as $fix) {
            $labels[] = $fix['label'] ?? 'Binaire';
            if (!empty($fix['description'])) {
                $descriptions[] = '- ' . $fix['label'] . ' : ' . $fix['description'];
            }
            if (!empty($fix['command'])) {
                $lines[] = (string) $fix['command'];
            }
        }

        $description = 'Elements concernes : ' . implode(', ', array_unique($labels)) . '.';
        if ($blocking) {
            $description .= ' Ces corrections destucknt le wizard.';
        }
        if (!empty($descriptions)) {
            $description .= "\n" . implode("\n", array_unique($descriptions));
        }

        return [
            'title'       => $title,
            'description' => $description,
            'command'     => implode("\n", array_unique($lines)),
        ];
    }

    private static function buildCommandDescription(array $labels, array $platform, string $suffix): string
    {
        $parts = [];
        if (!empty($labels)) {
            $parts[] = 'Elements manquants : ' . implode(', ', array_unique($labels)) . '.';
        }
        if (!empty($platform['pretty_name'])) {
            $parts[] = 'Commande preparee pour ' . $platform['pretty_name'] . '.';
        }
        if ($suffix !== '') {
            $parts[] = $suffix;
        }

        return implode(' ', $parts);
    }

    private static function collectPackages(array $entries, array $platform): array
    {
        $packages = [];
        $labels = [];

        foreach ($entries as $entry) {
            $key = (string) ($entry['key'] ?? '');
            $label = (string) ($entry['label'] ?? $key);
            if ($key === '') {
                continue;
            }

            $package = self::resolvePackageName($key, $platform);
            if ($package === null || $package === '') {
                continue;
            }

            $packages[] = $package;
            $labels[] = $label;
        }

        return [
            'packages' => array_values(array_unique($packages)),
            'labels'   => array_values(array_unique($labels)),
        ];
    }

    private static function resolvePackageName(string $key, array $platform): ?string
    {
        $manager = (string) ($platform['manager'] ?? 'unknown');

        switch ($key) {
            case 'restic':
                return 'restic';

            case 'ssh':
                return match ($manager) {
                    'apt', 'apk' => 'openssh-client',
                    'dnf', 'yum' => 'openssh-clients',
                    'zypper'     => 'openssh',
                    default      => 'openssh-client',
                };

            case 'rsync':
                return 'rsync';

            case 'php_cli':
                return match ($manager) {
                    'apt'    => self::aptPhpPrefix() . '-cli',
                    'apk'    => self::apkPhpPrefix() . '-cli',
                    'zypper' => self::zypperPhpPrefix() . '-cli',
                    default  => 'php-cli',
                };

            case 'pdo':
            case 'openssl':
            case 'json':
            case 'session':
                return match ($manager) {
                    'apt'    => self::aptPhpPrefix() . '-common',
                    'apk'    => self::apkPhpPrefix(),
                    'zypper' => self::zypperPhpPrefix(),
                    default  => 'php-common',
                };

            case 'mbstring':
                return match ($manager) {
                    'apt'    => self::aptPhpPrefix() . '-mbstring',
                    'apk'    => self::apkPhpPrefix() . '-mbstring',
                    'zypper' => self::zypperPhpPrefix() . '-mbstring',
                    default  => 'php-mbstring',
                };

            case 'curl':
                return match ($manager) {
                    'apt'    => self::aptPhpPrefix() . '-curl',
                    'apk'    => self::apkPhpPrefix() . '-curl',
                    'zypper' => self::zypperPhpPrefix() . '-curl',
                    default  => 'php-curl',
                };

            case 'pdo_sqlite':
                return match ($manager) {
                    'apt'    => self::aptPhpPrefix() . '-sqlite3',
                    'apk'    => self::apkPhpPrefix() . '-pdo_sqlite',
                    'zypper' => self::zypperPhpPrefix() . '-sqlite',
                    default  => 'php-sqlite3',
                };

            case 'pdo_mysql':
                return match ($manager) {
                    'apt'    => self::aptPhpPrefix() . '-mysql',
                    'apk'    => self::apkPhpPrefix() . '-pdo_mysql',
                    'zypper' => self::zypperPhpPrefix() . '-mysql',
                    default  => 'php-mysqlnd',
                };

            case 'pdo_pgsql':
                return match ($manager) {
                    'apt'    => self::aptPhpPrefix() . '-pgsql',
                    'apk'    => self::apkPhpPrefix() . '-pdo_pgsql',
                    'zypper' => self::zypperPhpPrefix() . '-pgsql',
                    default  => 'php-pgsql',
                };

            case 'gd':
                return match ($manager) {
                    'apt'    => self::aptPhpPrefix() . '-gd',
                    'apk'    => self::apkPhpPrefix() . '-gd',
                    'zypper' => self::zypperPhpPrefix() . '-gd',
                    default  => 'php-gd',
                };

            case 'zip':
                return match ($manager) {
                    'apt'        => self::aptPhpPrefix() . '-zip',
                    'apk'        => self::apkPhpPrefix() . '-zip',
                    'zypper'     => self::zypperPhpPrefix() . '-zip',
                    'dnf', 'yum' => 'php-pecl-zip',
                    default      => 'php-zip',
                };
        }

        return null;
    }

    private static function buildPackageInstallCommand(array $packages, array $platform): string
    {
        $packages = array_values(array_unique(array_filter($packages)));
        $packageList = implode(' ', $packages);
        $manager = (string) ($platform['manager'] ?? 'unknown');

        return match ($manager) {
            'apt'    => "sudo apt update\nsudo apt install -y {$packageList}",
            'dnf'    => "sudo dnf install -y {$packageList}",
            'yum'    => "sudo yum install -y {$packageList}",
            'apk'    => "sudo apk add {$packageList}",
            'zypper' => "sudo zypper install -y {$packageList}",
            default  => "# Installez les paquets equivalants avec votre gestionnaire habituel\n{$packageList}",
        };
    }

    public static function installRsync(?string $sudoPassword = null): array
    {
        $status = self::inspectBinary(self::SYSTEM_BINARIES['rsync']);
        if ($status['ok']) {
            return [
                'ok'        => true,
                'installed' => true,
                'message'   => 'rsync est deja disponible sur ce serveur.',
            ];
        }

        $platform = self::detectInstallPlatform();
        $package = self::resolvePackageName('rsync', $platform);
        $manualCommand = $package !== null && $package !== ''
            ? self::buildPackageInstallCommand([$package], $platform)
            : '';
        $commands = $package !== null && $package !== ''
            ? self::buildPackageInstallSteps([$package], $platform)
            : [];

        if ($commands === []) {
            return [
                'ok'                     => false,
                'installed'              => false,
                'message'                => 'Installation automatique indisponible sur ce serveur. Installez rsync manuellement, sinon le restore ne pourra pas fonctionner.',
                'manual_command'         => $manualCommand,
                'needs_sudo_password'    => false,
                'requires_manual_install'=> true,
            ];
        }

        $sudoPassword = is_string($sudoPassword) ? $sudoPassword : null;
        $runOptions = [
            'timeout' => 600,
        ];
        $env = self::buildPackageInstallEnv($platform);
        if ($env !== []) {
            $runOptions['env'] = $env;
        }

        $commandPrefix = [];
        if (!self::isRunningAsRoot()) {
            $sudoBinary = self::getSudoBinary();
            if ($sudoBinary === '') {
                return [
                    'ok'                     => false,
                    'installed'              => false,
                    'message'                => 'Le serveur requiert sudo pour installer rsync, mais le binaire sudo est introuvable. Installez rsync manuellement, sinon le restore ne pourra pas fonctionner.',
                    'manual_command'         => $manualCommand,
                    'needs_sudo_password'    => false,
                    'requires_manual_install'=> true,
                ];
            }

            if ($sudoPassword !== null && $sudoPassword !== '') {
                $commandPrefix = [$sudoBinary, '-S', '--'];
                $runOptions['stdin'] = $sudoPassword . "\n";
            } elseif (self::canUseSudoWithoutPassword($sudoBinary)) {
                $commandPrefix = [$sudoBinary, '-n', '--'];
            } else {
                return [
                    'ok'                  => false,
                    'installed'           => false,
                    'message'             => 'Un mot de passe sudo est requis pour installer rsync sur ce serveur.',
                    'manual_command'      => $manualCommand,
                    'needs_sudo_password' => true,
                ];
            }
        }

        $outputs = [];
        foreach ($commands as $command) {
            $result = ProcessRunner::run(array_merge($commandPrefix, $command), $runOptions);
            $outputs[] = '$ ' . ProcessRunner::renderCommand($command) . "\n" . trim((string) ($result['output'] ?? ''));
            if (!$result['success']) {
                unset($sudoPassword);
                return [
                    'ok'                  => false,
                    'installed'           => false,
                    'message'             => 'L installation de rsync a echoue. Vous pouvez reessayer ou l installer manuellement, sinon le restore ne pourra pas fonctionner.',
                    'manual_command'      => $manualCommand,
                    'needs_sudo_password' => false,
                    'output'              => trim(implode("\n\n", array_filter($outputs))),
                ];
            }
        }
        unset($sudoPassword);

        $status = self::inspectBinary(self::SYSTEM_BINARIES['rsync']);
        if (!$status['ok']) {
            return [
                'ok'                  => false,
                'installed'           => false,
                'message'             => 'rsync ne semble toujours pas disponible apres l installation. Verifiez le serveur avant d utiliser le restore.',
                'manual_command'      => $manualCommand,
                'needs_sudo_password' => false,
                'output'              => trim(implode("\n\n", array_filter($outputs))),
            ];
        }

        return [
            'ok'           => true,
            'installed'    => true,
            'message'      => 'rsync a ete installe sur le serveur. Le restore est maintenant disponible.',
            'output'       => trim(implode("\n\n", array_filter($outputs))),
        ];
    }

    private static function buildPackageInstallSteps(array $packages, array $platform): array
    {
        $packages = array_values(array_unique(array_filter(array_map('strval', $packages))));
        if ($packages === []) {
            return [];
        }

        $manager = (string) ($platform['manager'] ?? 'unknown');
        $managerBinary = self::getPackageManagerBinary($platform);
        if ($managerBinary === '') {
            return [];
        }

        return match ($manager) {
            'apt'    => [
                [$managerBinary, 'update'],
                array_merge([$managerBinary, 'install', '-y'], $packages),
            ],
            'dnf', 'yum' => [
                array_merge([$managerBinary, 'install', '-y'], $packages),
            ],
            'apk'    => [
                array_merge([$managerBinary, 'add'], $packages),
            ],
            'zypper' => [
                array_merge([$managerBinary, 'install', '-y'], $packages),
            ],
            default  => [],
        };
    }

    private static function buildPackageInstallEnv(array $platform): array
    {
        return ((string) ($platform['manager'] ?? '')) === 'apt'
            ? ['DEBIAN_FRONTEND' => 'noninteractive']
            : [];
    }

    private static function getPackageManagerBinary(array $platform): string
    {
        $configured = trim((string) ($platform['manager_binary'] ?? ''));
        if ($configured !== '') {
            return $configured;
        }

        return match ((string) ($platform['manager'] ?? 'unknown')) {
            'apt'    => (string) (self::findExecutable(['apt-get', 'apt']) ?? ''),
            'dnf'    => (string) (self::findExecutable(['dnf']) ?? ''),
            'yum'    => (string) (self::findExecutable(['yum']) ?? ''),
            'apk'    => (string) (self::findExecutable(['apk']) ?? ''),
            'zypper' => (string) (self::findExecutable(['zypper']) ?? ''),
            default  => '',
        };
    }

    private static function isRunningAsRoot(): bool
    {
        if (function_exists('posix_geteuid')) {
            return @posix_geteuid() === 0;
        }

        $user = strtolower(trim((string) getenv('USER')));
        return $user === 'root';
    }

    private static function getSudoBinary(): string
    {
        return ProcessRunner::locateBinary('sudo', ['/usr/bin/sudo', '/bin/sudo']);
    }

    private static function canUseSudoWithoutPassword(string $sudoBinary): bool
    {
        if ($sudoBinary === '') {
            return false;
        }

        $trueBinary = ProcessRunner::locateBinary('true', ['/usr/bin/true', '/bin/true']);
        $command = [$sudoBinary, '-n', '--', $trueBinary !== '' ? $trueBinary : 'true'];
        $result = ProcessRunner::run($command, ['timeout' => 10]);
        return $result['success'];
    }

    private static function detectInstallPlatform(): array
    {
        $data = [];
        if (is_file('/etc/os-release')) {
            foreach ((array) @file('/etc/os-release', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (!str_contains($line, '=')) {
                    continue;
                }
                [$key, $value] = explode('=', $line, 2);
                $data[strtolower(trim($key))] = trim($value, " \t\n\r\0\x0B\"'");
            }
        }

        $prettyName = (string) ($data['pretty_name'] ?? '');
        $id = strtolower((string) ($data['id'] ?? ''));
        $idLike = preg_split('/\s+/', strtolower((string) ($data['id_like'] ?? ''))) ?: [];
        $family = array_values(array_unique(array_filter(array_merge([$id], $idLike))));

        $manager = 'unknown';
        $managerBinary = '';
        foreach (['apt-get' => 'apt', 'dnf' => 'dnf', 'yum' => 'yum', 'apk' => 'apk', 'zypper' => 'zypper'] as $binary => $name) {
            $resolved = self::findExecutable([$binary]);
            if ($resolved !== null) {
                $manager = $name;
                $managerBinary = $resolved;
                break;
            }
        }

        if ($manager === 'unknown') {
            if (in_array('debian', $family, true) || in_array('ubuntu', $family, true)) {
                $manager = 'apt';
            } elseif (in_array('rhel', $family, true) || in_array('fedora', $family, true) || in_array('centos', $family, true)) {
                $manager = 'dnf';
            } elseif (in_array('alpine', $family, true)) {
                $manager = 'apk';
            } elseif (in_array('suse', $family, true) || in_array('opensuse', $family, true)) {
                $manager = 'zypper';
            }
        }

        return [
            'id'          => $id,
            'pretty_name' => $prettyName !== '' ? $prettyName : ($id !== '' ? $id : PHP_OS_FAMILY),
            'manager'     => $manager,
            'manager_binary' => $managerBinary !== '' ? $managerBinary : self::getPackageManagerBinary(['manager' => $manager]),
        ];
    }

    private static function findExecutable(array $candidates): ?string
    {
        $seen = [];
        $pathEnv = (string) getenv('PATH');
        $pathDirs = array_filter(explode(PATH_SEPARATOR, $pathEnv));

        foreach ($candidates as $candidate) {
            if (!is_string($candidate)) {
                continue;
            }

            $candidate = trim($candidate);
            if ($candidate === '' || isset($seen[$candidate])) {
                continue;
            }
            $seen[$candidate] = true;

            if (str_contains($candidate, '/') || str_contains($candidate, '\\')) {
                if (self::isExecutablePath($candidate)) {
                    return realpath($candidate) ?: $candidate;
                }
                continue;
            }

            foreach ($pathDirs as $dir) {
                $path = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $candidate;
                if (self::isExecutablePath($path)) {
                    return realpath($path) ?: $path;
                }
            }
        }

        return null;
    }

    private static function isExecutablePath(string $path): bool
    {
        return $path !== '' && is_file($path) && is_executable($path);
    }

    private static function aptPhpPrefix(): string
    {
        return 'php' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
    }

    private static function apkPhpPrefix(): string
    {
        return 'php' . PHP_MAJOR_VERSION . PHP_MINOR_VERSION;
    }

    private static function zypperPhpPrefix(): string
    {
        return 'php' . PHP_MAJOR_VERSION;
    }

    public static function testDatabaseConnection(array $params): array
    {
        $driver = $params['driver'] ?? 'sqlite';

        try {
            if ($driver === 'sqlite') {
                $dataDir = dirname(__DIR__, 2) . '/data';
                if (!is_dir($dataDir)) {
                    mkdir($dataDir, 0755, true);
                }
                $path = $dataDir . '/fulgurite.db';
                $pdo  = new PDO('sqlite:' . $path);
                $pdo->exec('PRAGMA user_version');
                return ['ok' => true, 'message' => 'Connexion SQLite réussie.', 'path' => $path];
            }

            if ($driver === 'mysql') {
                if (!extension_loaded('pdo_mysql')) {
                    return ['ok' => false, 'message' => "L'extension pdo_mysql n'est pas installée."];
                }
                $host    = $params['host'] ?? 'localhost';
                $port    = (int) ($params['port'] ?? 3306);
                $name    = $params['name'] ?? 'fulgurite';
                $user    = $params['user'] ?? '';
                $pass    = $params['pass'] ?? '';
                $charset = 'utf8mb4';
                $dsn     = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
                $pdo     = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $version = $pdo->query('SELECT VERSION()')->fetchColumn();
                return ['ok' => true, 'message' => "Connexion MySQL réussie — serveur : {$version}"];
            }

            if ($driver === 'pgsql') {
                if (!extension_loaded('pdo_pgsql')) {
                    return ['ok' => false, 'message' => "L'extension pdo_pgsql n'est pas installée."];
                }
                $host = $params['host'] ?? 'localhost';
                $port = (int) ($params['port'] ?? 5432);
                $name = $params['name'] ?? 'fulgurite';
                $user = $params['user'] ?? '';
                $pass = $params['pass'] ?? '';
                $dsn  = "pgsql:host={$host};port={$port};dbname={$name}";
                $pdo  = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $version = $pdo->query('SELECT version()')->fetchColumn();
                return ['ok' => true, 'message' => "Connexion PostgreSQL réussie — " . substr($version, 0, 60)];
            }

            return ['ok' => false, 'message' => 'Pilote inconnu : ' . $driver];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Erreur de connexion : ' . $e->getMessage()];
        }
    }

    // ── Step 3: Web server ─────────────────────────────────────────────────

    public static function detectWebServer(): array
    {
        $software = strtolower($_SERVER['SERVER_SOFTWARE'] ?? '');

        if (str_contains($software, 'apache')) {
            $detected = 'apache';
            $version  = $_SERVER['SERVER_SOFTWARE'] ?? 'Apache';
        } elseif (str_contains($software, 'nginx')) {
            $detected = 'nginx';
            $version  = $_SERVER['SERVER_SOFTWARE'] ?? 'Nginx';
        } elseif (str_contains($software, 'litespeed')) {
            $detected = 'apache'; // LiteSpeed is compatible Apache
            $version  = 'LiteSpeed (compatible Apache)';
        } else {
            $detected = 'unknown';
            $version  = $software ?: 'Inconnu';
        }

        return [
            'detected' => $detected,
            'version'  => $version,
            'web_user' => self::detectWebRuntimeUser($detected),
            'web_group' => self::detectWebRuntimeGroup($detected),
            'php_fpm_socket' => self::defaultPhpFpmSocket(),
            'htaccess_exists' => is_file(dirname(__DIR__, 2) . '/public/.htaccess'),
        ];
    }

    public static function generateApacheConfig(string $docRoot, string $serverName, string $webUser = '', string $webGroup = ''): string
    {
        $docRoot    = rtrim($docRoot, '/\\');
        $serverName = $serverName ?: 'fulgurite.example.com';
        $webUser = self::sanitizeSystemIdentity($webUser, self::detectWebRuntimeUser('apache'));
        $webGroup = self::sanitizeSystemIdentity($webGroup, $webUser);
        $secretAgent = self::generateSecretAgentSetupCommands($docRoot, $webUser, $webGroup);

        return <<<CONF
# Host virtuel Apache for Fulgurite# Copiez this bloc in votre file of configuration Apache# (ex: /etc/apache2/sites-available/fulgurite.conf)

<VirtualHost *:80>
    ServerName {$serverName}
    DocumentRoot {$docRoot}/public

    <Directory {$docRoot}/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/fulgurite_error.log
    CustomLog \${APACHE_LOG_DIR}/fulgurite_access.log combined
</VirtualHost>

# for HTTPS (recommande en production) :
# <VirtualHost *:443>
# ServerName {$serverName}
# DocumentRoot {$docRoot}/public
# SSLEngine on
# SSLCertificateFile /etc/ssl/certs/fulgurite.crt
# SSLCertificateKeyFile /etc/ssl/private/fulgurite.key
# <Directory {$docRoot}/public>
# AllowOverride All
# Require all granted
# </Directory>
# </VirtualHost>

{$secretAgent}
CONF;
    }

    public static function generateNginxConfig(string $docRoot, string $serverName, string $webUser = '', string $webGroup = '', string $phpFpmSocket = ''): string
    {
        $docRoot    = rtrim($docRoot, '/\\');
        $serverName = $serverName ?: 'fulgurite.example.com';
        $webUser = self::sanitizeSystemIdentity($webUser, self::detectWebRuntimeUser('nginx'));
        $webGroup = self::sanitizeSystemIdentity($webGroup, $webUser);
        $phpFpmSocket = self::sanitizeSocketPath($phpFpmSocket);
        $secretAgent = self::generateSecretAgentSetupCommands($docRoot, $webUser, $webGroup);

        return <<<CONF
# Bloc server Nginx for Fulgurite
# Copiez this file in /etc/nginx/sites-available/fulgurite# Then activez-the : ln -s /etc/nginx/sites-available/fulgurite /etc/nginx/sites-enabled/# Rechargez : nginx -s reload

server {
    listen 80;
    server_name {$serverName};
    root {$docRoot}/public;
    index index.php;
    charset utf-8;

    # security : stuckr the acces to the files caches    location ~ /\. { deny all; }

    # management of URL propres (equivalent.htaccess)
    location / {
        try_files \$uri \$uri/ \$uri.php?\$query_string;
    }

    # Suppression of the extension.php in the URLs
    location ~ ^/(?!api/|cron|worker)(.+)$ {
        try_files \$uri \$uri.php?\$query_string @phpfallback;
    }

    location @phpfallback {
        rewrite ^(.+)$ \$1.php last;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:{$phpFpmSocket};
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    # Cache of assets statiques
    location ~* \.(css|js|svg|ico|png|jpe?g|webp|woff2?)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Headers of security
    add_header X-Frame-Options DENY always;
    add_header X-Content-Type-Options nosniff always;
    add_header Referrer-Policy strict-origin-when-cross-origin always;

    access_log /var/log/nginx/fulgurite_access.log;
    error_log  /var/log/nginx/fulgurite_error.log;
}

{$secretAgent}
CONF;
    }

    // ── Finalisation ────────────────────────────────────────────────────────

    private static function detectWebRuntimeUser(string $serverType = ''): string
    {
        if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
            $info = posix_getpwuid(posix_geteuid());
            if (!empty($info['name'])) {
                return (string) $info['name'];
            }
        }

        $candidate = trim((string) ($_SERVER['USER'] ?? $_SERVER['APACHE_RUN_USER'] ?? ''));
        if ($candidate !== '') {
            return self::sanitizeSystemIdentity($candidate, 'www-data');
        }

        return match ($serverType) {
            'apache', 'nginx' => 'www-data',
            default => 'www-data',
        };
    }

    private static function detectWebRuntimeGroup(string $serverType = ''): string
    {
        if (function_exists('posix_getgrgid') && function_exists('posix_getegid')) {
            $info = posix_getgrgid(posix_getegid());
            if (!empty($info['name'])) {
                return (string) $info['name'];
            }
        }

        $candidate = trim((string) ($_SERVER['APACHE_RUN_GROUP'] ?? ''));
        if ($candidate !== '') {
            return self::sanitizeSystemIdentity($candidate, 'www-data');
        }

        return self::detectWebRuntimeUser($serverType);
    }

    private static function defaultPhpFpmSocket(): string
    {
        $version = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        return '/run/php/php' . $version . '-fpm.sock';
    }

    private static function sanitizeSystemIdentity(string $value, string $fallback): string
    {
        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }

        return preg_match('/^[a-z_][a-z0-9_-]{0,31}$/i', $value) === 1 ? $value : $fallback;
    }

    private static function sanitizeSocketPath(string $value): string
    {
        $value = trim($value);
        if ($value === '' || !str_starts_with($value, '/')) {
            return self::defaultPhpFpmSocket();
        }

        return preg_match('/^[A-Za-z0-9_\/.\-]+$/', $value) === 1 ? $value : self::defaultPhpFpmSocket();
    }

    private static function generateSecretAgentSetupCommands(string $docRoot, string $webUser, string $webGroup): string
    {
        $docRoot = rtrim($docRoot, '/\\');
        $phpBin = self::findExecutable(['/usr/bin/php', '/usr/local/bin/php', 'php']) ?? '/usr/bin/php';

        return <<<CONF

# Secret broker local Fulgurite
# Recommande: utilisez a user PHP-FPM dedie a Fulgurite, by exemple fulgurite-web.
# if {$webUser} is compromis, il can demander a secret by reference exacte to the broker,# mais il ne can not lire the cle maitre ni the stockage brut of broker.
sudo getent group {$webGroup} >/dev/null || sudo groupadd --system {$webGroup}
sudo useradd --system --home {$docRoot} --shell /usr/sbin/nologin --gid {$webGroup} {$webUser} 2>/dev/null || true
sudo useradd --system --home /var/lib/fulgurite-secrets --shell /usr/sbin/nologin fulgurite-secrets 2>/dev/null || true
sudo chown -R {$webUser}:{$webGroup} {$docRoot}
sudo find {$docRoot} -type d -exec chmod 0750 {} \;
sudo find {$docRoot} -type f -exec chmod 0640 {} \;
sudo find {$docRoot}/bin {$docRoot}/scripts -type f -exec chmod 0750 {} \; 2>/dev/null || true
sudo install -d -m 0700 -o fulgurite-secrets -g fulgurite-secrets /var/lib/fulgurite-secrets
sudo install -d -m 0750 -o root -g fulgurite-secrets /etc/fulgurite
sudo {$phpBin} {$docRoot}/bin/fulgurite-secret-key | sudo tee /etc/fulgurite/secret-agent.key >/dev/null
sudo chown root:fulgurite-secrets /etc/fulgurite/secret-agent.key
sudo chmod 0640 /etc/fulgurite/secret-agent.key

sudo tee /etc/systemd/system/fulgurite-secret-agent.service >/dev/null <<'UNIT_EOF'
[Unit]
Description=Fulgurite local secret broker
After=network.target

[Service]
Type=simple
User=fulgurite-secrets
Group={$webGroup}
SupplementaryGroups=fulgurite-secrets
RuntimeDirectory=fulgurite
RuntimeDirectoryMode=0770
UMask=0007
Environment=FULGURITE_SECRET_AGENT_SOCKET=/run/fulgurite/secrets.sock
Environment=FULGURITE_SECRET_AGENT_DB=/var/lib/fulgurite-secrets/secrets.db
Environment=FULGURITE_SECRET_AGENT_KEY_FILE=/etc/fulgurite/secret-agent.key
Environment=FULGURITE_SECRET_AGENT_AUDIT=/var/lib/fulgurite-secrets/audit.log
ExecStart={$phpBin} {$docRoot}/bin/fulgurite-secret-agent
Restart=on-failure
RestartSec=2
NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=full
ProtectHome=true
ReadWritePaths=/run/fulgurite /var/lib/fulgurite-secrets

[Install]
WantedBy=multi-user.target
UNIT_EOF

sudo systemctl daemon-reload
sudo systemctl enable --now fulgurite-secret-agent

sudo -u {$webUser} {$phpBin} -r '\$s=stream_socket_client("unix:///run/fulgurite/secrets.sock",\$e,\$m,2); if(!\$s){fwrite(STDERR,"ERR \$e \$m\n"); exit(1);} fwrite(\$s, "{\"action\":\"health\",\"purpose\":\"health\"}\n"); echo fgets(\$s);'

# Option HA reseau (cluster of brokers with backend partage)
# 1. Deployez the same unite on plusieurs nodes en adaptant :# - FULGURITE_SECRET_AGENT_BIND=tcp://0.0.0.0:9876
# - FULGURITE_SECRET_AGENT_PUBLIC_ENDPOINT=tcp://broker-1.example.net:9876
# - FULGURITE_SECRET_AGENT_NODE_ID=broker-1
# - FULGURITE_SECRET_AGENT_NODE_LABEL=broker-1
# - FULGURITE_SECRET_AGENT_CLUSTER_NAME=fulgurite-secret-broker
# - FULGURITE_SECRET_AGENT_CLUSTER_PEERS=tcp://broker-2.example.net:9876,tcp://broker-3.example.net:9876
# - FULGURITE_SECRET_AGENT_DB=/var/lib/fulgurite-secrets/secrets.db (backend partage, or path/DSN equivalent monte on each node)#
# 2. Cote application, configurez ensuite a endpoint logique unique or plusieurs endpoints :
# FULGURITE_SECRET_BROKER_ENDPOINTS=tcp://broker-1.example.net:9876,tcp://broker-2.example.net:9876,tcp://broker-3.example.net:9876
#
# 3. Gardez the socket unix local if vous voulez conserver the acces local for diagnostic,
# mais the client PHP basculera en priorite on FULGURITE_SECRET_BROKER_ENDPOINTS lorsqu'il is defini.
CONF;
    }

    /**
     * Validates and sanitizes data from all wizard forms.
     */
    public static function validateFinalData(array $data): array
    {
        $errors = [];

        // Admin
        $username = trim($data['admin_username'] ?? '');
        $password = $data['admin_password'] ?? '';
        $email    = trim($data['admin_email'] ?? '');

        if (strlen($username) < 3) {
            $errors[] = "Le nom d'utilisateur doit faire at least 3 caractères.";
        }
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $username)) {
            $errors[] = "Le nom d'utilisateur ne peut contenir que des lettres, chiffres, _, - et .";
        }
        if (strlen($password) < 8) {
            $errors[] = "Le mot de passe doit faire at least 8 caractères.";
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'adresse email n'est pas valide.";
        }

        // App settings
        $appName = trim($data['app_name'] ?? '');
        if ($appName === '') {
            $errors[] = "Le nom de l'application est requis.";
        }

        foreach (['web_user' => 'utilisateur PHP-FPM', 'web_group' => 'groupe PHP-FPM'] as $key => $label) {
            $value = trim((string) ($data[$key] ?? ''));
            if ($value !== '' && preg_match('/^[a-z_][a-z0-9_-]{0,31}$/i', $value) !== 1) {
                $errors[] = "Le {$label} contient des caracteres invalides.";
            }
        }

        $socket = trim((string) ($data['php_fpm_socket'] ?? ''));
        if ($socket !== '' && (!str_starts_with($socket, '/') || preg_match('/^[A-Za-z0-9_\/.\-]+$/', $socket) !== 1)) {
            $errors[] = "Le socket PHP-FPM doit etre un chemin absolu valide.";
        }

        return $errors;
    }

    /**
     * Execute the installation complete.
     */
    public static function finalize(array $data): array
    {
        try {
            $rootDir  = dirname(__DIR__, 2);
            $dataDir  = $rootDir . '/data';
            $driver   = $data['db_driver'] ?? 'sqlite';

            // 1. create required directories
            foreach (['', '/passwords', '/ssh_keys', '/cache', '/themes'] as $sub) {
                $dir = $dataDir . $sub;
                if (!is_dir($dir)) {
                    if (!mkdir($dir, 0755, true)) {
                        return ['ok' => false, 'message' => "Impossible de créer le répertoire {$dir}."];
                    }
                }
            }

            // 2. Write DB configuration into .env (outside repository)
            $envPath = $rootDir . '/.env';
            $dbConfigPath = $envPath;
            $envWritten = self::writeDatabaseEnv($envPath, $data);
            if (!$envWritten) {
                self::applyDatabaseEnvValues($data);
                if (is_writable($rootDir)) {
                    return ['ok' => false, 'message' => "Impossible d'écrire {$dbConfigPath}. Vérifiez les permissions."];
                }

                // Rootfs is read-only: continue with runtime-injected configuration
                // (Docker / process environment) without requiring local write.
            }

            // 3. Initialiser the base of data
            self::initDatabase($data);

            // 4. create the user admin
            self::createAdminUser($data);

            // 5. Sauvegarder the settings applicatifs
            self::applyAppSettings($data);

            // 6. Generate the policy filesystem en read single for the runtime normal
            FilesystemScopeGuard::writeCurrentPolicyFile();

            // 7. Verrouiller definitivement the setup and supprimer the bootstrap token
            if (!SetupGuard::finalizeInstall($driver)) {
                return ['ok' => false, 'message' => "Impossible de verrouiller l'installation. Verifiez les permissions du dossier data/."]; 
            }

            return ['ok' => true, 'message' => 'Installation terminée avec succès.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Erreur lors de l\'installation : ' . $e->getMessage()];
        }
    }

    // ── Methodes privees ────────────────────────────────────────────────────

    private static function writeDatabaseEnv(string $envPath, array $data): bool
    {
        $values = self::databaseEnvValues($data);

        $existing = is_file($envPath) ? (string) file_get_contents($envPath) : '';
        $content = self::mergeEnvValues($existing, $values);
        if (@file_put_contents($envPath, $content) === false) {
            return false;
        }

        self::applyEnvValues($values);
        return true;
    }

    private static function databaseEnvValues(array $data): array
    {
        $driver = $data['db_driver'] ?? 'sqlite';

        return [
            'DB_DRIVER' => $driver,
            'DB_PATH' => dirname(__DIR__, 2) . '/data/fulgurite.db',
            'SEARCH_DB_PATH' => dirname(__DIR__, 2) . '/data/fulgurite-search.db',
            'DB_HOST' => $data['db_host'] ?? 'localhost',
            'DB_PORT' => (string) ($data['db_port'] ?? ($driver === 'pgsql' ? '5432' : '3306')),
            'DB_NAME' => $data['db_name'] ?? 'fulgurite',
            'DB_USER' => $data['db_user'] ?? '',
            'DB_PASS' => $data['db_pass'] ?? '',
            'DB_CHARSET' => 'utf8mb4',
            'FULGURITE_WEB_USER' => self::sanitizeSystemIdentity((string) ($data['web_user'] ?? ''), self::detectWebRuntimeUser()),
            'FULGURITE_WEB_GROUP' => self::sanitizeSystemIdentity((string) ($data['web_group'] ?? ''), self::sanitizeSystemIdentity((string) ($data['web_user'] ?? ''), self::detectWebRuntimeGroup())),
            'FULGURITE_PHP_FPM_SOCKET' => self::sanitizeSocketPath((string) ($data['php_fpm_socket'] ?? '')),
            'FULGURITE_SECRET_PROVIDER' => 'agent',
            'FULGURITE_SECRET_AGENT_SOCKET' => '/run/fulgurite/secrets.sock',
        ];
    }

    private static function applyDatabaseEnvValues(array $data): void
    {
        self::applyEnvValues(self::databaseEnvValues($data));
    }

    private static function applyEnvValues(array $values): void
    {
        foreach ($values as $key => $value) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    private static function mergeEnvValues(string $existing, array $values): string
    {
        $seen = [];
        $lines = preg_split('/\R/', $existing) ?: [];

        foreach ($lines as $index => $line) {
            if (!str_contains($line, '=')) {
                continue;
            }

            [$key] = explode('=', $line, 2);
            $key = trim($key);
            if (!array_key_exists($key, $values)) {
                continue;
            }

            $lines[$index] = $key . '=' . self::formatEnvValue((string) $values[$key]);
            $seen[$key] = true;
        }

        $additions = [];
        foreach ($values as $key => $value) {
            if (!isset($seen[$key])) {
                $additions[] = $key . '=' . self::formatEnvValue((string) $value);
            }
        }

        if (!empty($additions)) {
            if (!empty($lines) && trim(implode('', $lines)) !== '') {
                $lines[] = '';
            }
            $lines[] = '# Base de donnees Fulgurite';
            array_push($lines, ...$additions);
        }

        return rtrim(implode(PHP_EOL, $lines)) . PHP_EOL;
    }

    private static function formatEnvValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/^[A-Za-z0-9_.,:\/@+-]+$/', $value) === 1) {
            return $value;
        }

        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
    }

    private static function initDatabase(array $data): void
    {
        require_once __DIR__ . '/../DatabaseMigrations.php';

        $driver = $data['db_driver'] ?? 'sqlite';

        if ($driver === 'sqlite') {
            $dbPath = dirname(__DIR__, 2) . '/data/fulgurite.db';
            $pdo    = new PDO('sqlite:' . $dbPath);
            $pdo->exec('PRAGMA journal_mode = WAL');
            $pdo->exec('PRAGMA busy_timeout = 5000');
            // Ensure DB constants are defined before explicit migration.
            if (!defined('DB_DRIVER')) define('DB_DRIVER', 'sqlite');
            if (!defined('DB_PATH')) define('DB_PATH', $dbPath);
            if (!defined('SEARCH_DB_PATH')) define('SEARCH_DB_PATH', dirname(__DIR__, 2) . '/data/fulgurite-search.db');
        } else {
            // For MySQL/PostgreSQL, configuration comes from the environment.
            // Migrations are executed explicitly here.
            if (!defined('DB_DRIVER')) define('DB_DRIVER', $driver);
            if (!defined('DB_HOST')) define('DB_HOST', $data['db_host'] ?? 'localhost');
            if (!defined('DB_PORT')) define('DB_PORT', $data['db_port'] ?? '3306');
            if (!defined('DB_NAME')) define('DB_NAME', $data['db_name'] ?? 'fulgurite');
            if (!defined('DB_USER')) define('DB_USER', $data['db_user'] ?? '');
            if (!defined('DB_PASS')) define('DB_PASS', $data['db_pass'] ?? '');
            if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');
        }

        DatabaseMigrations::migrateConfiguredDatabases();
    }

    private static function createAdminUser(array $data): void
    {
        $username  = trim($data['admin_username'] ?? 'admin');
        $password  = $data['admin_password'] ?? '';
        $email     = trim($data['admin_email'] ?? '');
        $firstName = trim($data['admin_first_name'] ?? '');
        $lastName  = trim($data['admin_last_name'] ?? '');

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $db   = Database::getInstance();
        $now  = Database::nowExprPublic();

        $stmt = $db->prepare("
            INSERT INTO users (username, password, role, email, first_name, last_name, password_set_at, created_at)
            VALUES (?, ?, 'admin', ?, ?, ?, {$now}, {$now})
        ");
        $stmt->execute([$username, $hash, $email ?: null, $firstName ?: null, $lastName ?: null]);
    }

    private static function applyAppSettings(array $data): void
    {
        $settings = [
            'app_name'        => $data['app_name'] ?? 'Fulgurite',
            'app_timezone'    => $data['timezone'] ?? 'UTC',
        ];

        foreach ($settings as $key => $value) {
            Database::setSetting($key, (string) $value);
        }
    }
}
