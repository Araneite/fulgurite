<?php

namespace App\Enums;

enum StartPage :string
{
    case HomePage = "dashboard";
    case ProfilePage = "profile";
    case LogsPage = "logs";
    
    public function labelKey(): string {
        return match ($this) {
            self::HomePage => "pages/list.start_page.dashboard",
            self::ProfilePage => "pages/list.start_page.profile",
            self::LogsPage => "pages/list.start_page.logs",
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
            fn (self $startPage)=> [
                "label"=> $startPage->label(),
                "value"=> $startPage->value
            ],
            self::cases()
        );
    }
}
