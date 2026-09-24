<?php

namespace App\Enums\User;

enum UserPermissions: string
{
    // Users permissions - Each permission is related to roles scope
    case view = 'users:view'; // View one or many users 
    case viewSensitive = 'users:view-sensitive'; // View sensitive data of one or many users
    case create = 'users:create'; // Create a new user
    case update = 'users:update'; // Update a user
    case manage = 'users:manage'; // Update sensitive/security data of a user
    case delete = 'users:delete'; // Delete a user
    case restore = 'users:restore'; // Restore a user from soft delete
    case forceDelete = 'users:force-delete'; // Delete permanently a user
    
    public function labelKey(): string {
        return match ($this) {
            self::view => 'permissions.view',
            self::viewSensitive => 'permissions.view-sensitive',
            self::create => 'permissions.create',
            self::update => 'permissions.update',
            self::manage => 'permissions.manage',
            self::delete => 'permissions.delete',
            self::restore => 'permissions.restore',
            self::forceDelete => 'permissions.force-delete',
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
            fn (self $permission)=> [
                'label'=> $permission->label(),
                'value'=> $permission->value,
            ],
            self::cases()
        );
    }
}
