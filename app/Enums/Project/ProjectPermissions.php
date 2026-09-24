<?php

namespace App\Enums\Project;

enum ProjectPermissions: string
{
    case view = 'projects:view'; // List/View projet data - When on global role, list and view each projects otherwise view only scoped project
    case create = 'projects:create'; // Create a new project - Only on global scope
    case update = 'projects:update'; // Update a project - When global role, update all projects otherwise only scoped project
    case manage = 'projects:manage'; // Manage a project - When global role, manage all projects otherwise only scoped project
    case delete = 'projects:delete'; // Delete scoped project - Only on global scope
    case restore = 'projects:restore'; // Restore a soft deleted project - Only on global scope
    case forceDelete = 'projects:force-delete'; // Delete permanently a project - Only on global scope
    
    public function labelKey(): string {
        return match ($this) {
            self::view => 'permissions.view',
            self::create => 'permissions.create',
            self::update => 'permissions.update',
            self::manage => 'permissions.manage',
            self::delete => 'permissions.delete',
            self::restore => 'permissions.restore',
            self::forceDelete => 'permissions:force-delete',
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
