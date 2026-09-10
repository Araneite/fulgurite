<?php

namespace App\Enums\User;

enum Status: string
{
    case active = 'active';
    case locked = 'locked';
    case expired = 'expired';
    case suspended = 'suspended';
    case trashed = 'trashed';
    
    public function labelKey(): string {
        return match ($this) {
            self::active => 'users/status.active',
            self::locked => 'users/status.locked',
            self::expired => 'users/status.expired',
            self::suspended => 'users/status.suspended',
            self::trashed => 'users/status.trashed',
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
            fn (self $status) => [
                'label'=> $status->label(),
                'value'=> $status->value,
            ],
            self::cases()
        );
    }
}
