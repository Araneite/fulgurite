<?php

namespace App\Services\Authorization;

use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use InvalidArgumentException;

final class PermissionChecker
{
    public function allows(
        User $user,
        string $permission,
        ?Project $project = null,
    ): bool {
        $this->validatePermission($permission);
        
        if ($this->hasSystemPermission($user, $permission)) return true;
        
        if ($project === null) return false;
        
        return $this->hasProjectPermission(
            user: $user, project: $project, permission: $permission
        );
    }
    
    private function rolesAllow(
        iterable $roles,
        string $permission,
    ): bool {
        foreach ($roles as $role) {
            if ($this->roleAllow($role, $permission)) return true;
        }
        
        return false;
    }
    
    private function roleAllow(
        Role $role,
        string $permission,
    ): bool {
        $permissions = $role->permissions ?? [];
        
        if (in_array($permission, $permissions, true)) return true;
        
        $wildcard = $this->wildcardFor($permission);
        
        return in_array($wildcard, $permissions, true);
    }
    
    private function wildcardFor(string $permission): string {
        [$resource] = explode(':', $permission, 2);
        
        return "{$resource}:*";
    }
    
    private function validatePermission(string $permission): void {
        if (!preg_match('/^[a-z0-9_-]+:[a-z0-9_*-]+$/i', $permission)) {
            throw new InvalidArgumentException("Invalid permission format: {$permission}");
        }
    }
    
    private function getRoles(User $user) {
        $user->loadMissing('roles');

        return $user->roles;
    }
    
    public function hasSystemPermission(
        User $user,
        string $permission
    ): bool {
        $this->validatePermission($permission);
        
        return $this->rolesAllow(
            roles: $this->getRoles($user)->filter(
                fn(Role $role)=> $role->isSystem()
            ),
            permission: $permission
        );
    }
    public function hasProjectPermission(
        User $user,
        Project $project,
        string $permission
    ): bool {
        $this->validatePermission($permission);
        
        return $this->rolesAllow(
            roles: $this->getRoles($user)->filter(
                fn (Role $role)=> 
                    $role->isProject()
                    && $role->project_id === $project->getKey()
            ),
            permission: $permission
        );
    }
    
    public function authorizedProjectIds(
        User $user,
        string $permission
    ): array {
        $this->validatePermission($permission);
        
        return $this->getRoles($user)->filter(
            fn (Role $role) =>
                $role->isProject()
                && $this->roleAllow($role, $permission)
        )
            ->pluck('project_id')
            ->unique()
            ->values()
            ->all();
    }
}
