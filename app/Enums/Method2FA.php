<?php

namespace App\Enums;

enum Method2FA: string
{
    case oneTimeCode = "one_time_code";
    case email = "email";
    case passkey = "passkey";
    
    public function labelKey(): string {
        return match ($this) {
            self::oneTimeCode => "auth/two-factor.methods.one_time_code",
            self::email => "auth/two-factor.methods.email",
            self::passkey => "auth/two-factor.methods.passkey",
        };
    }
    
    public function label(): string {
        return trans($this->labelKey());
    }
    
    public static function values(): array {
        return array_column(self::cases(), "value");
    }
    public static function options(): array {
        return array_map(
            fn ($method)=> [
                "label"=> $method->label(),
                "value"=> $method->value
            ],
            self::cases()
        );
    }
}
