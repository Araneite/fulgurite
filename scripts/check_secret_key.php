<?php
// Check format of secret master key at /etc/fulgurite/secret.key
$path = '/etc/fulgurite/secret.key';
if (!file_exists($path)) {
    echo "MISSING: $path not found\n";
    exit(2);
}
$contents = trim(file_get_contents($path));
if ($contents === '') {
    echo "INVALID: file empty\n";
    exit(3);
}
// Base64 32 bytes -> length 44 with padding '=' or 43/44
if (preg_match('/^[A-Za-z0-9+\/]+=*$/', $contents) && (strlen(base64_decode($contents, true)) === 32)) {
    echo "OK: master key is valid base64 (32 bytes)\n";
    exit(0);
}
// Hex 64 chars
if (preg_match('/^[0-9a-fA-F]{64}$/', $contents)) {
    echo "OK: master key is valid hex (64 chars)\n";
    exit(0);
}
// Otherwise invalid
echo "INVALID: master key format invalid. Use 32 bytes base64 or 64 hex chars.\n";
exit(4);
