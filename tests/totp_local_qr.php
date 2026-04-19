<?php

declare(strict_types=1);

define('FULGURITE_CLI', true);

$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . '/fulgurite-totp-test-' . bin2hex(random_bytes(4));
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

function fail(string $message): void {
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function assertTrueValue(bool $value, string $message): void {
    if (!$value) {
        fail($message);
    }
}

function assertSameValue(mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        fail(
            $message
            . "\nExpected: " . var_export($expected, true)
            . "\nActual: " . var_export($actual, true)
        );
    }
}

$secret = 'JBSWY3DPEHPK3PXP';
$otpAuthUrl = Totp::getOtpAuthUrl($secret, 'alice', 'Fulgurite');

assertTrueValue(str_starts_with($otpAuthUrl, 'otpauth://totp/Fulgurite%3Aalice?'), 'The otpauth URI label should be RFC-compliant.');
assertTrueValue(str_contains($otpAuthUrl, 'secret=' . $secret), 'The otpauth URI should include the secret.');
assertTrueValue(str_contains($otpAuthUrl, 'issuer=Fulgurite'), 'The otpauth URI should include the issuer.');

$svg = Totp::renderQrCodeSvg($otpAuthUrl);

assertTrueValue(str_starts_with($svg, '<svg '), 'The QR renderer should return inline SVG markup.');
assertTrueValue(str_contains($svg, 'aria-label="TOTP QR code"'), 'The inline SVG should include accessibility metadata.');
assertTrueValue(!str_contains($svg, 'api.qrserver.com'), 'The QR renderer must not depend on external QR services.');
assertTrueValue(!str_contains($svg, $secret), 'The inline SVG must not embed the TOTP secret as text.');
assertTrueValue(!str_contains($svg, rawurlencode($otpAuthUrl)), 'The inline SVG must not embed the otpauth URI.');

$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'alice';
$_SESSION['role'] = 'admin';

$setup = Auth::totpSetupStart();

assertSameValue(array_key_exists('qr_url', $setup), false, 'The setup payload must not expose an external QR URL.');
assertSameValue(array_key_exists('otp_url', $setup), false, 'The setup payload must not expose the otpauth URI.');
assertSameValue($_SESSION['totp_setup_secret'], $setup['secret'], 'The pending setup secret should stay server-side in the session.');
assertTrueValue(isset($setup['qr_svg']) && str_starts_with((string) $setup['qr_svg'], '<svg '), 'The setup payload should expose inline SVG.');
assertSameValue('alice', substr((string) $setup['label'], -5), 'The setup payload should expose the label metadata.');

echo "TOTP local QR tests OK.\n";
