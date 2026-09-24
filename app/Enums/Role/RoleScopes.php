<?php

namespace App\Enums\Role;

enum RoleScopes: string
{
    case System = 'system';
    case Project = 'project';
    
    public function labelKey(): string {
        return match ($this) {
            self::System => 'roles/scopes.system',
            self::Project => 'roles/scopes.project',
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
            fn (self $scope) => [
                'label'=> $scope->label(),
                'value'=> $scope->value
            ],
            self::cases()
        );
    }
}
