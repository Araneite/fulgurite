<?php
// =============================================================================
// Totp.php — TOTP implementation (RFC 6238) without external dependencies
// =============================================================================

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\QRCodeException;
use chillerlan\QRCode\Output\QRCodeOutputException;

class Totp {

    private static function ensureQrCodeLibraryLoaded(): void {
        if (class_exists(QROptions::class, false) && class_exists(QRCode::class, false)) {
            return;
        }

        $autoloadPath = __DIR__ . '/ThirdParty/ChillerlanQrAutoload.php';

        if (is_file($autoloadPath)) {
            require_once $autoloadPath;
        }

        if (!class_exists(QROptions::class) || !class_exists(QRCode::class)) {
            throw new RuntimeException('Local QR code library is not available.');
        }
    }

    // ── Generate a random base32 secret ──────────────────────────────────────
    public static function generateSecret(): string {
        $chars  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 32; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }

    // ── Verify a TOTP code ────────────────────────────────────────────────────
    public static function verify(string $secret, string $code, int $window = 1): bool {
        $code = preg_replace('/\s/', '', $code);
        if (!preg_match('/^\d{6}$/', $code)) return false;

        $timestamp = (int) floor(time() / 30);
        for ($i = -$window; $i <= $window; $i++) {
            if (self::getCode($secret, $timestamp + $i) === $code) {
                return true;
            }
        }
        return false;
    }

    // ── Generate the code for a given timestamp ───────────────────────────────
    public static function getCode(string $secret, ?int $timestamp = null): string {
        if ($timestamp === null) {
            $timestamp = (int) floor(time() / 30);
        }
        $key  = self::base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timestamp);
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $otp = (
            ((ord($hash[$offset])   & 0x7F) << 24) |
            ((ord($hash[$offset+1]) & 0xFF) << 16) |
            ((ord($hash[$offset+2]) & 0xFF) << 8)  |
            (ord($hash[$offset+3]) & 0xFF)
        ) % 1000000;
        return str_pad((string) $otp, 6, '0', STR_PAD_LEFT);
    }

    // ── otpauth URL for QR code ───────────────────────────────────────────────
    public static function getOtpAuthUrl(string $secret, string $username, string $issuer = ''): string {
        $issuer = trim($issuer !== '' ? $issuer : AppConfig::totpIssuer());
        $label = ($issuer !== '' ? $issuer . ':' : '') . $username;
        $params = [
            'secret' => $secret,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => 30,
        ];

        if ($issuer !== '') {
            $params['issuer'] = $issuer;
        }

        return 'otpauth://totp/'. rawurlencode($label)
            . '?'
            . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    public static function getSetupPayload(string $secret, string $username, string $issuer = ''): array {
        $issuer = trim($issuer !== '' ? $issuer : AppConfig::totpIssuer());
        $label = ($issuer !== '' ? $issuer . ':' : '') . $username;
        $otpAuthUrl = self::getOtpAuthUrl($secret, $username, $issuer);

        return [
            'secret' => $secret,
            'issuer' => $issuer,
            'label' => $label,
            'qr_svg' => self::renderQrCodeSvg($otpAuthUrl),
        ];
    }

    public static function renderQrCodeSvg(string $otpAuthUrl, int $size = 220): string {
        self::ensureQrCodeLibraryLoaded();

        $options = new QROptions([
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
        ]);

        try {
            $svg = (string) (new QRCode($options))->render($otpAuthUrl);
        } catch (QRCodeException | QRCodeOutputException $e) {
            throw new RuntimeException('Unable to generate local TOTP QR code.', 0, $e);
        }

        $size = max(96, min($size, 512));
        $svg = preg_replace(
            '/<svg\b/',
            sprintf('<svg width="%1$d" height="%1$d" role="img" aria-label="TOTP QR code"', $size),
            $svg,
            1
        );

        if (!is_string($svg) || $svg === '') {
            throw new RuntimeException('Unable to finalize local TOTP QR code.');
        }

        return $svg;
    }

    // ── Decode base32 ────────────────────────────────────────────────────────
    private static function base32Decode(string $input): string {
        $map     = array_flip(str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'));
        $input   = strtoupper(rtrim($input, '='));
        $buffer  = 0;
        $bitsLeft = 0;
        $result  = '';

        foreach (str_split($input) as $char) {
            if (!isset($map[$char])) continue;
            $buffer    = ($buffer << 5) | $map[$char];
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $result   .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }
        return $result;
    }
}
