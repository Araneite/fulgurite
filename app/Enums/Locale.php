<?php

namespace App\Enums;

enum Locale: string
{
    case fr_FR = "fr_FR";
    case en_US = "en_US";
    
    public function labelKey(): string {
        return match($this) {
            self::fr_FR => "locales.fr_FR",
            self::en_US => "locales.en_US",
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
            fn (self $locale)=> [
                "label"=> $locale->label(),
                "value"=> $locale->value
            ],
            self::cases()
        );
    }
}
