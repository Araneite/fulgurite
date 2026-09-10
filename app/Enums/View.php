<?php

namespace App\Enums;

enum View: string
{
    case Active = "active";
    case Trash = "trash";
    case All = "all";
    
    public function labelKey(): string {
        return match ($this) {
            self::Active => "global/views.active",
            self::Trash => "global/views.trash",
            self::All => "global/views.all",
        };
    }
    
    public function label(): string {
        return trans($this->labelKey());
    }
    
    public static function values(): array {
        return array_column(self::cases(), 'value');
    }
    
    public static function options(): array {
        return array_map(
            fn (self $view)=> [
                "label"=> $view->label(),
                "value"=> $view->value,
            ],
            self::cases()
        );
    }
}
