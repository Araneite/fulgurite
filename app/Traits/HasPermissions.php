<?php

namespace App\Traits;

trait HasPermissions
{
    public function getPermissions(): array {
        $permissions = $this->role->permissions ?? [];
        
        if (!is_array($permissions)) {
            return [];
        }
        
        return array_map(
            fn ($permission) => $this->normalizePermission($permission),
                $permissions
        );
    }
    
    public function hasPermission(string $permission): bool {
        $permission = $this->normalizePermission($permission);
        
        if (!$permission) {
            return false;
        }
        
        $permissions = $this->getPermissions();
        
        if (in_array('*', $permissions, true)) {
            return true;
        }
        
        if (in_array($permission, $permissions, true)) {
            return true;
        }
        
        [$resource] = array_pad(explode(':', $permission, 2), 2, null);
        
        if ($resource && in_array($resource . ":*", $permissions, true)) {
            return true;
        }
        
        return false;
    }
    
    public function hasAnyPermission(array $permissions): bool {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        
        return false;
    }
    
    public function hasAllPermissions(array $permissions): bool {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        
        return true;
    }
    
    protected function normalizePermission($permission): array|string|null
    {
        if (!is_string($permission)) {
            return null;
        }
        
        $permission = trim(strtolower($permission));
        
        if ($permission === '') {
            return null;
        }
        
        $permission = preg_replace('/\s*:\s*/', ':', $permission);
        
        return $permission;
    }
}
