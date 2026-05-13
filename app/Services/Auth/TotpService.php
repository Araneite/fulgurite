<?php

namespace App\Services\Auth;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use App\Models\User;
use Illuminate\Support\Str;

class TotpService
{
	public function generateSecret(int $length = 32): string {
        $alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
        $secret = "";
        
        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        
        return $secret;
    }
    
    public function provisionUri(User $user, string $secret): string {
        $issuer = config("app.name", "Fulgurite");
        $label = rawurlencode($issuer . ":" . $user->email);
        
        return sprintf(
            "otpath://totp/%s&issuer=%s&algorithm=sha1&digits=6&period=30",
            $label,
            $secret, 
            rawurlencode($issuer)
        );
    }
    
    public function provisioningUri(User $user, string $secret): string
    {
        $issuer = config('app.name', 'Fulgurite');
        $account = $user->email;
        
        return 'otpauth://totp/'
            . rawurlencode($issuer) . ':' . rawurlencode($account)
            . '?secret=' . rawurlencode($secret)
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1'
            . '&digits=6'
            . '&period=30';
    }
    
    
    public function qrCodeSvg(string $uri): string {
        $renderer = new ImageRenderer(
            new RendererStyle(220),
            new SvgImageBackend()
        );
        
        return (new Writer($renderer))->writeString($uri);
    }
    
    public function verify(string $secret, string $code): bool {
        foreach([-1, 0, 1] as $windows) {
            if (hash_equals($this->totp($secret, time() + ($windows * 30)), $code)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function totp(string $secret, int $timestamp): string {
        $counter = intdiv($timestamp, 30);
        $key = $this->base32Decode($secret);
        $binaryCounter = pack("N*", 0) . pack("N*", $counter);
        $hash = hash_hmac("sha1", $binaryCounter, $key, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $value = unpack("N", substr($hash, $offset, 4))[1] & 0x7FFFFFFF;
        
        return str_pad((string) ($value % 1000000), 6, "0", STR_PAD_LEFT);
    }
    
    private function base32Decode(string $secret): string {
        $alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', "", $secret));
        $bits = "";
        
        foreach (str_split($secret) as $char) {
            $bits .= str_pad(decbin(strpos($alphabet, $char)), 5, "0", STR_PAD_LEFT);
        }
        
        $bytes = "";
        
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $bytes .= chr(bindec($byte));
            }
        }
        
        return $bytes;
    }
}
