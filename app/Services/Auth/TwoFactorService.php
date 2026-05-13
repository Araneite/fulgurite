<?php

namespace App\Services\Auth;

use App\Enums\Method2FA;
use App\Mail\TwoFactorEmailCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Mockery\Generator\Method;

class TwoFactorService
{
    public function availableMethods(User $user): array
    {
        $settings = $user->settings;
        
        if (!$settings) {
            return [];
        }
        
        $enabledMethods = collect($settings->enabledSecondFactorMethods());
        $availableMethods = Method2FA::options();
        
        return collect($availableMethods)
            ->filter(function (array $method) use ($enabledMethods, $user) {
                if (!$enabledMethods->contains($method['value'])) {
                    return false;
                }
                
                if ($method['value'] === 'passkey') {
//                    return $user->webAuthnCredentials()->whereEnabled()->exists();
                    return true;
                }
                
                return true;
            })
            ->sortBy(fn (array $method) => $method['value'] === $settings->primary_second_factor ? 0 : 1)
            ->values()
            ->all();
    }
    
    public function sendEmailCode(User $user): void {
        $code = (string) random_int(100000, 999999);
        
        Cache::put($this->emailCacheKey($user), hash("sha256", $code), now()->addMinutes(10));
        
        Mail::to($user->email)->queue(new TwoFactorEmailCodeMail($code));
    }
    
    public function verify(User $user, string $method, ?string $code): bool {
        return match ($method) {
            "email"=> $this->verifyEmailCode($user, $code),
            "one_time_code"=> $this->verifyTotpCode($user, $code),
            default => false,
        };
    }
    
    public function verifyEmailCode(User $user, ?string $code): bool {
        if (!$code) return false;
        
        $exceptedHash = Cache::get($this->emailCacheKey($user));
        
        if (!$exceptedHash || !hash_equals($exceptedHash, hash("sha256", $code))) return false;
        
        Cache::forget($this->emailCacheKey($user));
        
        return true;
    }
    
    public function verifyTotpCode(User $user, string $code): bool {
        if (!$code || !$user->settings?->totp_secret) return false;
        
        $secret = $user->settings->totp_secret;
        
        foreach([-1, 0, 1] as $window) {
            if (hash_equals($this->totp($secret, time() + (window * 30)), $code)) {
                return true;
            }
        }
        
        return false;
    }
    
    public function maskedEmail(string $email): string {
        [$name, $domain] = explode("@", $email, 2);
        
        return Str::mask($name, "*", 2) . "@" . $domain;
    }
    
    private function totp(string $secret, int $timestamp): string {
        $counter = intdiv($timestamp, 30);
        $key = $this->base32Decode($secret);
        $binaryCounter = pack("N", 0) . pack("N*", $counter);
        $hash = hash_hmac("sha1", $binaryCounter, $key, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $value = unpack("N", substr($hash, $offset, 4))[1] & 0x7FFFFFFF;
        
        return str_pad((string) ($value % 1000000), 6, "0", STR_PAD_LEFT);
    }
    
    private function base32Decode(string $secret): string {
        $alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret));
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
    
    private function emailCacheKey(User $user): string {
        return "two_factor:email:{$user->id}";
    }
    
    private function passkeyCacheKey(User $user): string {
        return "two_factor:passkey:{$user->id}";
    }
}
