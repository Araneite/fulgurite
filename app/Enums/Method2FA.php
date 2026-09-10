<?php

namespace App\Enums;

enum Method2FA: string
{
    case oneTimeCode = "one_time_code";
    case email = "email";
    case passkey = "passkey";
    
    public function labelKey(): string {
        return match ($this) {
            self::oneTimeCode => "forms/confirmation-identity.methods.one_time_code",
            self::email => "forms/confirmation-identity.methods.email",
            self::passkey => "forms/confirmation-identity.methods.passkey",
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
