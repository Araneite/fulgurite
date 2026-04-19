<?php
// =============================================================================
// WebAuthn.php - Hardware key authentication (FIDO2 / WebAuthn)
// Lightweight implementation without external dependencies
// Supports: USB keys (YubiKey, etc.), Touch ID, Face ID, Windows Hello
// =============================================================================

class WebAuthn {

    // ── Constantes ────────────────────────────────────────────────────────────
    private const ALGO_ES256 = -7;   // ECDSA P-256
    private const ALGO_RS256 = -257; // RSA PKCS#1 v1.5

    // ── Registration step 1: generate challenge ────────────────────────────────
    public static function registrationOptions(int $userId, string $username): array {
        $challenge = base64_encode(random_bytes(32));
        $_SESSION['webauthn_reg_challenge'] = $challenge;
        $_SESSION['webauthn_reg_user_id']   = $userId;
        $rpId = self::rpId();

        return [
            'challenge' => $challenge,
            'rp' => [
                'name' => AppConfig::webauthnRpName(),
                'id'   => $rpId,
            ],
            'user' => [
                'id'          => base64_encode((string) $userId),
                'name'        => $username,
                'displayName' => $username,
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => self::ALGO_ES256],
                ['type' => 'public-key', 'alg' => self::ALGO_RS256],
            ],
            'authenticatorSelection' => [
                'userVerification'   => AppConfig::webauthnUserVerification(),
                'residentKey'        => AppConfig::webauthnResidentKey(),
                'requireResidentKey' => AppConfig::webauthnRequireResidentKey(),
            ],
            'timeout'     => AppConfig::webauthnRegistrationTimeoutMs(),
            'attestation' => AppConfig::webauthnAttestation(),
        ];
    }

    // ── Registration step 2: verify and store key ─────────────────────────────
    public static function registrationVerify(array $credential, string $keyName): array {
        try {
            if (empty($_SESSION['webauthn_reg_challenge'])) {
                throw new RuntimeException('Session de registration expirée.');
            }

            $clientDataJSON  = self::decodeBinary($credential['clientDataJSON'] ?? '');
            $attestationObj  = self::decodeBinary($credential['attestationObject'] ?? '');
            $clientData      = json_decode($clientDataJSON, true);

            // Check the type
            if (($clientData['type'] ?? '') !== 'webauthn.create') {
                throw new RuntimeException('Type clientData invalide.');
            }

            // Check the challenge
            $receivedChallenge = self::decodeBinary($clientData['challenge'] ?? '');
            $expectedChallenge = base64_decode($_SESSION['webauthn_reg_challenge'], true);
            // Compare decoded bytes
            if ($expectedChallenge === false || !hash_equals($expectedChallenge, $receivedChallenge)) {
                throw new RuntimeException('Challenge invalide.');
            }

            // Check the origine
            $origin = $clientData['origin'] ?? '';
            if (!self::isAllowedOrigin($origin)) {
                throw new RuntimeException("Origine invalide: $origin");
            }

            // Parser the attestationObject (CBOR simplifie)
            $authData = self::parseAttestationObject($attestationObj);
            $parsedAuthData = self::parseAuthenticatorData($authData, true);
            self::assertRpIdHashMatches($parsedAuthData['rp_id_hash']);
            self::assertFlags($parsedAuthData['flags'], true, self::registrationRequiresUserVerification());

            // extract the key public from authData
            $publicKey = self::extractPublicKey($authData);
            if (!$publicKey) {
                throw new RuntimeException('Impossible d\'extraire la clé publique.');
            }

            $credentialId = self::base64UrlEncode(self::extractCredentialId($authData));
            $transports = self::encodeStoredTransports($credential['transports'] ?? []);

            // Stocker en base
            $userId = $_SESSION['webauthn_reg_user_id'] ?? ($_SESSION['user_id'] ?? null);
            if (!$userId) throw new RuntimeException('Utilisateur non identifié.');

            Database::getInstance()->prepare("
                INSERT INTO webauthn_credentials (user_id, name, credential_id, public_key, transports, counter, counter_supported, use_count, last_used_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, NULL)
            ")->execute([
                $userId,
                $keyName,
                $credentialId,
                self::encodeStoredPublicKey($publicKey),
                $transports,
                $parsedAuthData['sign_count'],
                $parsedAuthData['sign_count'] > 0 ? 1 : 0,
            ]);

            unset($_SESSION['webauthn_reg_challenge'], $_SESSION['webauthn_reg_user_id']);
            StepUpAuth::syncPrimaryFactor((int) $userId);
            return ['success' => true];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ── Authentication step 1: generate challenge ───────────────────────
    public static function authOptions(int $userId, array $context = []): array {
        $challenge = base64_encode(random_bytes(32));
        $requestContext = [
            'mode' => (string) ($context['mode'] ?? 'login'),
            'operation' => trim(strtolower((string) ($context['operation'] ?? 'login'))),
            'user_id' => $userId,
            'challenge' => $challenge,
            'time' => time(),
            'require_user_verification' => !empty($context['require_user_verification']),
        ];
        $_SESSION['webauthn_auth_request'] = $requestContext;

        // Recuperer the credential IDs of the user
        $stmt = Database::getInstance()->prepare(
            "SELECT credential_id, transports FROM webauthn_credentials WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
        $allowCredentials = [];
        foreach ($stmt->fetchAll() as $row) {
            $credentialId = trim((string) ($row['credential_id'] ?? ''));
            if ($credentialId === '') {
                continue;
            }

            $entry = [
                'type' => 'public-key',
                'id'   => $credentialId,
            ];

            $transports = self::decodeStoredTransports($row['transports'] ?? null);
            if (!empty($transports)) {
                $entry['transports'] = $transports;
            }

            $allowCredentials[] = $entry;
        }

        return [
            'challenge'        => $challenge,
            'allowCredentials' => $allowCredentials,
            'userVerification' => $requestContext['require_user_verification'] ? 'required' : AppConfig::webauthnUserVerification(),
            'timeout'          => AppConfig::webauthnAuthTimeoutMs(),
            'rpId'             => self::rpId(),
        ];
    }

    // ── Authentication step 2: verify signature ─────────────────────
    public static function authVerify(array $assertion): array {
        try {
            $request = $_SESSION['webauthn_auth_request'] ?? null;
            if (!is_array($request) || empty($request['challenge'])) {
                throw new RuntimeException('Session d\'authentification expirée.');
            }
            if ((time() - (int) ($request['time'] ?? 0)) > AppConfig::secondFactorPendingTtlSeconds()) {
                throw new RuntimeException('Session d\'authentification expirée.');
            }

            $credentialId = trim((string) ($assertion['rawId'] ?? $assertion['id'] ?? ''));
            $credentialIdRaw = $credentialId !== '' ? self::decodeOptionalBinary($credentialId) : '';
            $clientDataJSON  = self::decodeBinary($assertion['clientDataJSON'] ?? '');
            $authenticatorData = self::decodeBinary($assertion['authenticatorData'] ?? '');
            $signature       = self::decodeBinary($assertion['signature'] ?? '');
            $clientData      = json_decode($clientDataJSON, true);
            if (!is_array($clientData)) {
                throw new RuntimeException('clientDataJSON invalide.');
            }

            if (($clientData['type'] ?? '') !== 'webauthn.get') {
                throw new RuntimeException('Type clientData invalide.');
            }

            // Check the challenge
            $receivedChallenge = self::decodeBinary($clientData['challenge'] ?? '');
            $expectedChallenge = base64_decode((string) $request['challenge'], true);
            if ($expectedChallenge === false || !hash_equals($expectedChallenge, $receivedChallenge)) {
                throw new RuntimeException('Challenge invalide.');
            }

            // Check the origine
            $origin = $clientData['origin'] ?? '';
            if (!self::isAllowedOrigin($origin)) {
                throw new RuntimeException("Origine invalide: $origin");
            }

            // Retrouver the credential en base
            $userId = (int) ($request['user_id'] ?? 0);
            $stmt   = Database::getInstance()->prepare(
                "SELECT * FROM webauthn_credentials WHERE user_id = ?"
            );
            $stmt->execute([$userId]);
            $cred = null;
            foreach ($stmt->fetchAll() as $row) {
                $storedId = trim((string) ($row['credential_id'] ?? ''));
                $storedRaw = $storedId !== '' ? self::decodeOptionalBinary($storedId) : '';
                $matchesRaw = $credentialIdRaw !== '' && $storedRaw !== '' && hash_equals($storedRaw, $credentialIdRaw);
                $matchesText = $credentialId !== '' && $storedId !== '' && hash_equals($storedId, $credentialId);
                if ($matchesRaw || $matchesText) {
                    $cred = $row;
                    break;
                }
            }
            if (!$cred) throw new RuntimeException('Clé inconnue.');

            $publicKey = self::decodeStoredPublicKey($cred['public_key'] ?? '');
            if (!is_array($publicKey) || empty($publicKey)) {
                throw new RuntimeException('Cle WebAuthn invalide ou incomplete. Supprimez-la puis reenregistrez-la.');
            }

            $parsedAuthData = self::parseAuthenticatorData($authenticatorData, false);
            self::assertRpIdHashMatches($parsedAuthData['rp_id_hash']);
            self::assertFlags($parsedAuthData['flags'], true, !empty($request['require_user_verification']));

            $userHandle = trim((string) ($assertion['userHandle'] ?? ''));
            if ($userHandle !== '') {
                $decodedUserHandle = self::decodeBinary($userHandle);
                if (!hash_equals((string) $userId, $decodedUserHandle)) {
                    throw new RuntimeException('Assertion WebAuthn non coherente avec l utilisateur attendu.');
                }
            }

            // Check the signature
            $clientDataHash = hash('sha256', $clientDataJSON, true);
            $verifyData     = $authenticatorData . $clientDataHash;

            if (!self::verifySignature($verifyData, $signature, $publicKey)) {
                throw new RuntimeException('Signature invalide.');
            }

            self::updateCountersAfterSuccessfulAssertion($cred, $parsedAuthData['sign_count'], $userId);

            unset($_SESSION['webauthn_auth_request']);
            return [
                'success' => true,
                'user_id' => (int) $userId,
                'context' => [
                    'mode' => (string) ($request['mode'] ?? 'login'),
                    'operation' => (string) ($request['operation'] ?? 'login'),
                ],
            ];

        } catch (Exception $e) {
            unset($_SESSION['webauthn_auth_request']);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ── Liste of keys of a user ──────────────────────────────────────
    public static function getUserCredentials(int $userId): array {
        $stmt = Database::getInstance()->prepare(
            "SELECT id, name, created_at, counter, counter_supported, use_count, last_used_at FROM webauthn_credentials WHERE user_id = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function userHasCredentials(int $userId): bool {
        $stmt = Database::getInstance()->prepare("SELECT COUNT(*) FROM webauthn_credentials WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ── Supprimer a key ─────────────────────────────────────────────────────
    public static function deleteCredential(int $credId, int $userId): bool {
        $stmt = Database::getInstance()->prepare(
            "DELETE FROM webauthn_credentials WHERE id = ? AND user_id = ?"
        );
        $stmt->execute([$credId, $userId]);
        $deleted = $stmt->rowCount() > 0;
        if ($deleted) {
            StepUpAuth::syncPrimaryFactor($userId);
        }
        return $deleted;
    }

    // ── Helpers internes ──────────────────────────────────────────────────────

    private static function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }

    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function decodeBinary(string $data): string {
        if ($data === '') return '';
        $normalized = strtr($data, '-_', '+/');
        $normalized .= str_repeat('=', (4 - strlen($normalized) % 4) % 4);
        $decoded = base64_decode($normalized, true);
        if ($decoded === false) {
            throw new RuntimeException('Encodage binaire WebAuthn invalide.');
        }
        return $decoded;
    }

    private static function decodeOptionalBinary(string $data): string {
        try {
            return self::decodeBinary($data);
        } catch (RuntimeException $e) {
            return '';
        }
    }

    private static function rpId(): string {
        $override = AppConfig::webauthnRpIdOverride();
        if ($override !== '') {
            return $override;
        }

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $host = preg_replace('/:\d+$/', '', $host) ?: 'localhost';
        return strtolower($host);
    }

    private static function expectedOrigin(): string {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://'. self::rpId();
    }

    private static function encodeStoredPublicKey(array $publicKey): string {
        $json = json_encode(self::encodeBinaryValues($publicKey), JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            throw new RuntimeException('Impossible de serialiser la cle publique WebAuthn.');
        }
        return $json;
    }

    private static function decodeStoredPublicKey(string $stored): ?array {
        if ($stored === '') {
            return null;
        }

        $decoded = json_decode($stored, true);
        if (!is_array($decoded)) {
            return null;
        }

        $restored = self::decodeBinaryValues($decoded);
        return is_array($restored) ? $restored : null;
    }

    private static function encodeStoredTransports(mixed $transports): ?string {
        if (!is_array($transports)) {
            return null;
        }

        $allowed = ['ble', 'hybrid', 'internal', 'nfc', 'smart-card', 'usb'];
        $normalized = [];
        foreach ($transports as $transport) {
            if (!is_string($transport)) {
                continue;
            }

            $value = trim(strtolower($transport));
            if ($value === '' || !in_array($value, $allowed, true)) {
                continue;
            }

            $normalized[$value] = $value;
        }

        if (empty($normalized)) {
            return null;
        }

        $json = json_encode(array_values($normalized), JSON_UNESCAPED_SLASHES);
        return is_string($json) ? $json : null;
    }

    private static function decodeStoredTransports(?string $stored): array {
        if (!is_string($stored) || trim($stored) === '') {
            return [];
        }

        $decoded = json_decode($stored, true);
        if (!is_array($decoded)) {
            return [];
        }

        $allowed = ['ble', 'hybrid', 'internal', 'nfc', 'smart-card', 'usb'];
        $transports = [];
        foreach ($decoded as $transport) {
            if (!is_string($transport)) {
                continue;
            }

            $value = trim(strtolower($transport));
            if ($value !== '' && in_array($value, $allowed, true)) {
                $transports[$value] = $value;
            }
        }

        return array_values($transports);
    }

    private static function parseAuthenticatorData(string $authenticatorData, bool $requireAttestedCredentialData): array {
        if (strlen($authenticatorData) < 37) {
            throw new RuntimeException('authenticatorData invalide.');
        }

        $flagsByte = ord($authenticatorData[32]);
        $flags = [
            'up' => (bool) ($flagsByte & 0x01),
            'uv' => (bool) ($flagsByte & 0x04),
            'be' => (bool) ($flagsByte & 0x08),
            'bs' => (bool) ($flagsByte & 0x10),
            'at' => (bool) ($flagsByte & 0x40),
            'ed' => (bool) ($flagsByte & 0x80),
        ];

        if ($requireAttestedCredentialData && !$flags['at']) {
            throw new RuntimeException('Les donnees attestees WebAuthn sont absentes.');
        }

        return [
            'rp_id_hash' => substr($authenticatorData, 0, 32),
            'flags' => $flags,
            'sign_count' => unpack('N', substr($authenticatorData, 33, 4))[1] ?? 0,
        ];
    }

    private static function assertRpIdHashMatches(string $rpIdHash): void {
        $expected = hash('sha256', self::rpId(), true);
        if (!hash_equals($expected, $rpIdHash)) {
            throw new RuntimeException('rpIdHash invalide.');
        }
    }

    private static function assertFlags(array $flags, bool $requireUserPresence, bool $requireUserVerification): void {
        if ($requireUserPresence && empty($flags['up'])) {
            throw new RuntimeException('Presence utilisateur WebAuthn requise.');
        }
        if ($requireUserVerification && empty($flags['uv'])) {
            throw new RuntimeException('Verification utilisateur WebAuthn requise.');
        }
    }

    private static function isAllowedOrigin(string $origin): bool {
        $parsed = parse_url($origin);
        if (!is_array($parsed)) {
            return false;
        }

        $host = strtolower((string) ($parsed['host'] ?? ''));
        $scheme = strtolower((string) ($parsed['scheme'] ?? ''));
        if ($host !== self::rpId()) {
            return false;
        }

        if ($scheme === 'https') {
            return true;
        }

        return $scheme === 'http' && in_array($host, ['localhost', '127.0.0.1'], true);
    }

    private static function registrationRequiresUserVerification(): bool {
        return AppConfig::webauthnUserVerification() === 'required';
    }

    private static function updateCountersAfterSuccessfulAssertion(array $credential, int $signCount, int $userId): void {
        $storedCounter = (int) ($credential['counter'] ?? 0);
        $counterSupported = (int) ($credential['counter_supported'] ?? ($storedCounter > 0 ? 1 : 0)) === 1;

        if ($signCount > 0) {
            if ($counterSupported && $storedCounter > 0 && $signCount <= $storedCounter) {
                Auth::log('webauthn_replay', "Compteur regressif — possible clonage de cle (user #$userId)", 'critical');
                throw new RuntimeException('Compteur de securite invalide — cle potentiellement clonee.');
            }

            $storedCounter = $signCount;
            $counterSupported = true;
        } elseif ($counterSupported && $storedCounter > 0) {
            Auth::log('webauthn_replay', "Compteur WebAuthn incoherent apres progression (user #$userId)", 'critical');
            throw new RuntimeException('Compteur de securite incoherent — assertion refusee.');
        }

        Database::getInstance()->prepare(
            "UPDATE webauthn_credentials
             SET counter = ?, counter_supported = ?, use_count = COALESCE(use_count, 0) + 1, last_used_at = datetime('now')
             WHERE id = ?"
        )->execute([$storedCounter, $counterSupported ? 1 : 0, $credential['id']]);
    }

    private static function encodeBinaryValues(mixed $value): mixed {
        if (is_array($value)) {
            $encoded = [];
            foreach ($value as $key => $item) {
                $encoded[$key] = self::encodeBinaryValues($item);
            }
            return $encoded;
        }

        if (is_string($value)) {
            return ['__binary' => self::base64UrlEncode($value)];
        }

        return $value;
    }

    private static function decodeBinaryValues(mixed $value): mixed {
        if (!is_array($value)) {
            return $value;
        }

        if (array_key_exists('__binary', $value) && count($value) === 1 && is_string($value['__binary'])) {
            return self::decodeBinary($value['__binary']);
        }

        $decoded = [];
        foreach ($value as $key => $item) {
            $decoded[$key] = self::decodeBinaryValues($item);
        }
        return $decoded;
    }

    /**
     * Parser CBOR minimaliste for attestationObject.
     * extracts authData from the map CBOR { "fmt":..., "attStmt":..., "authData":... }
     */
    private static function parseAttestationObject(string $data): string {
        // the premier byte indique a map CBOR (0xa0-0xbf for map)
        $pos = 0;
        $map = self::cborDecode($data, $pos);
        if (isset($map['authData'])) return $map['authData'];
        throw new RuntimeException('authData absent dans attestationObject.');
    }

    private static function cborDecode(string $data, int &$pos): mixed {
        if ($pos >= strlen($data)) throw new RuntimeException('CBOR: fin prématurée');
        $byte     = ord($data[$pos++]);
        $major    = ($byte >> 5) & 0x07;
        $info     = $byte & 0x1f;
        $length   = self::cborLength($data, $pos, $info);

        return match($major) {
            0 => $length,                                           // unsigned int
            1 => -1 - $length,                                      // negative int
            2 => (function() use ($data, &$pos, $length) {         // bytes
                $chunk = substr($data, $pos, $length);
                $pos += $length;
                return $chunk;
            })(),
            3 => (function() use ($data, &$pos, $length) {         // string
                $chunk = substr($data, $pos, $length);
                $pos += $length;
                return $chunk;
            })(),
            4 => array_map(fn() => self::cborDecode($data, $pos), array_fill(0, $length, null)), // array
            5 => (function() use ($data, &$pos, $length) {         // map
                $map = [];
                for ($i = 0; $i < $length; $i++) {
                    $k = self::cborDecode($data, $pos);
                    $v = self::cborDecode($data, $pos);
                    $map[$k] = $v;
                }
                return $map;
            })(),
            default => throw new RuntimeException("CBOR: type majeur non supporte ($major)"),
        };
    }

    private static function cborLength(string $data, int &$pos, int $info): int {
        if ($info < 24) return $info;
        if ($info === 24) return ord($data[$pos++]);
        if ($info === 25) { $v = unpack('n', substr($data, $pos, 2))[1]; $pos += 2; return $v; }
        if ($info === 26) { $v = unpack('N', substr($data, $pos, 4))[1]; $pos += 4; return $v; }
        return 0;
    }

    /**
     * extracts the key public COSE from authData (bytes 55+)
     * authData: rpIdHash(32) + flags(1) + counter(4) + attestedCredentialData(var)
     * attestedCredentialData: aaguid(16) + credIdLen(2) + credId(n) + coseKey(var)
     */
    private static function extractPublicKey(string $authData): ?array {
        if (strlen($authData) < 55) return null;
        $offset   = 32 + 1 + 4; // rpIdHash + flags + counter
        $flags    = ord($authData[32]);
        if (!($flags & 0x40)) return null; // AT (attested credential data) non present

        $aaguid   = substr($authData, $offset, 16); $offset += 16;
        $credIdLen = unpack('n', substr($authData, $offset, 2))[1]; $offset += 2;
        $offset  += $credIdLen; // skip credentialId

        // What remains is the COSE public key (CBOR map)
        $coseKey  = substr($authData, $offset);
        $pos      = 0;
        $keyMap   = self::cborDecode($coseKey, $pos);

        return $keyMap ?: null;
    }

    private static function extractCredentialId(string $authData): string {
        if (strlen($authData) < 55) return '';
        $offset   = 32 + 1 + 4 + 16; // rpIdHash + flags + counter + aaguid
        $credIdLen = unpack('n', substr($authData, $offset, 2))[1];
        return substr($authData, $offset + 2, $credIdLen);
    }

    /**
     * checks the signature ECDSA P-256 or RSA.
     * $publicKey : COSE key map (CBOR decode)
     */
    private static function verifySignature(string $data, string $sig, array $publicKey): bool {
        $alg = $publicKey[3] ?? self::ALGO_ES256;

        if ($alg === self::ALGO_ES256) {
            // ECDSA P-256 : key public = x (kty=-2) + y (kty=-3)
            $x = $publicKey[-2] ?? '';
            $y = $publicKey[-3] ?? '';
            if (!$x || !$y) return false;

            // Construire the key public DER
            $pem = self::ecPublicKeyToPem($x, $y);
            $key = openssl_pkey_get_public($pem);
            if (!$key) return false;

            return openssl_verify($data, $sig, $key, OPENSSL_ALGO_SHA256) === 1;
        }

        if ($alg === self::ALGO_RS256) {
            $n = $publicKey[-1] ?? '';
            $e = $publicKey[-2] ?? '';
            if (!$n || !$e) return false;
            $pem = self::rsaPublicKeyToPem($n, $e);
            $key = openssl_pkey_get_public($pem);
            if (!$key) return false;
            return openssl_verify($data, $sig, $key, OPENSSL_ALGO_SHA256) === 1;
        }

        return false;
    }

    private static function ecPublicKeyToPem(string $x, string $y): string {
        // EC public key DER: sequence{ sequence{ oid ecPublicKey, oid P-256 }, bitstring{ 04 || x || y } }
        $oid   = "\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07";
        $point = "\x04" . str_pad($x, 32, "\x00", STR_PAD_LEFT) . str_pad($y, 32, "\x00", STR_PAD_LEFT);
        $bs    = "\x03" . self::derLength(strlen($point) + 1) . "\x00" . $point;
        $seq   = "\x30" . self::derLength(strlen($oid) + strlen($bs)) . $oid . $bs;
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($seq), 64) . "-----END PUBLIC KEY-----";
    }

    private static function rsaPublicKeyToPem(string $n, string $e): string {
        $modulus  = "\x02" . self::derLength(strlen($n) + 1) . "\x00" . $n;
        $exponent = "\x02" . self::derLength(strlen($e)) . $e;
        $seq      = "\x30" . self::derLength(strlen($modulus) + strlen($exponent)) . $modulus . $exponent;
        $bitStr   = "\x03" . self::derLength(strlen($seq) + 1) . "\x00" . $seq;
        $oid      = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";
        $full     = "\x30" . self::derLength(strlen($oid) + strlen($bitStr)) . $oid . $bitStr;
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($full), 64) . "-----END PUBLIC KEY-----";
    }

    private static function derLength(int $len): string {
        if ($len < 128) return chr($len);
        if ($len < 256) return "\x81" . chr($len);
        return "\x82" . chr($len >> 8) . chr($len & 0xff);
    }
}
