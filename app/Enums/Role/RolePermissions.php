<?php

namespace App\Enums\Role;

enum RolePermissions: string
{
    // Roles permissions - Each permission is related to roles scope
    case view = 'roles:view'; // view one or many roles
    case create = 'roles:create'; // Create a new role on project related to project and hierarchy
    case update = 'roles:update'; // Update data of an existing role, related to project (only work on role under current user higher role)
    case grant = 'roles:grant'; // Add role to a user, related to project (only work if the role is under current user higher role)
    case revoke = 'roles:revoke'; // Remove role to a user, related to project (only work if the role is under current user higher role)  
    case manage = 'roles:manage'; // Manage permissions of a role, related to project and hierarchy
    case delete = 'roles:delete'; // Delete a role, related to project and hierarchy
    case restore = 'roles:restore'; // Restore a role, related to project and hierarchy
    case forceDelete = 'roles:force-delete'; // Force the deletion of role data, related to project and hierarchy
    
    public function labelKey(): string {
        return match($this) {
            self::view => 'permissions.view',
            self::create => 'permissions.create',
            self::update => 'permissions.update',
            self::grant => 'permissions.grant',
            self::revoke => 'permissions:revoke',
            self::manage => 'permissions:manage',
            self::delete => 'permissions:delete',
            self::restore => 'permissions:restore',
            self::forceDelete => 'permissions:force-delete',
        };
    }
    
    public function label(): string {
        return trans($this->labelKey());
    }
    
    public function values(): array {
        return array_column(self::cases(), 'value');
    }
    
    public function options(): array {
        return array_map(
            fn (self $permission)=> [
                'label' => $permission->label(),
                'value'=> $permission->value
            ],
            self::cases()
        );
    }
}

