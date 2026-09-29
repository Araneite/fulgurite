<?php

namespace App\Policies;

use App\Enums\Role\RolePermissions;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\PermissionChecker;
use App\Services\Authorization\RoleHierarchyChecker;

class RolePolicy
{
    public function __construct(
        private readonly PermissionChecker $permissions,
        private readonly RoleHierarchyChecker $hierarchy
    ) {}
    
    public function viewAny(User $user, ?Project $project = null): bool {
        return $this->permissions->allows(
            user: $user,
            permission: RolePermissions::view->value,
            project: $project
        );
    }
    
    public function view(User $user, Role $target): bool {
        return $this->allowsActionOnRole(
            user: $user,
            target: $target,
            permission: RolePermissions::view->value,
            checkHierarchy: false
        );
    }
    
    public function create(User $user, Project $project): bool {        
        return $this->permissions->allows(
            user: $user,
            permission: RolePermissions::create->value,
            project: $project
        );
    }
    
    public function update(User $user, Role $target): bool {
        if ($target->locked) return false;
        
        return $this->allowsActionOnRole(
            user: $user,
            target: $target,
            permission: RolePermissions::update->value,
        );
    } 
    
    public function manage(User $user, Role $target): bool {
        if ($target->locked) return false;
        
        return $this->allowsActionOnRole(
            user: $user,
            target: $target,
            permission: RolePermissions::manage->value,
        );
    }
    
    public function assign(User $user, Role $target): bool {
        return $this->allowsActionOnRole(
            user: $user,
            target: $target,
            permission: RolePermissions::grant->value,
        );
    }
    public function grant(User $user, Role $target, User $subject): bool {
        if (!$this->allowsActionOnRole(
            user: $user,
            target: $target,
            permission: RolePermissions::grant->value,
        )) return false;
        
        if ($user->is($subject)) return false;
        
        if ($this->hasRole($subject, $target)) return false;
        
        return $this->canManageUserForRole(
            user: $user,
            subject: $subject,
            targetRole: $target,
        );
    }

    public function revoke(
        User $user,
        Role $target,
        User $subject,
    ): bool {
        if (!$this->allowsActionOnRole(
            user: $user,
            target: $target,
            permission: RolePermissions::revoke->value,
        )) return false;
        
        if ($user->is($subject)) return false;
        
        if (!$this->hasRole($subject, $target)) return false;
        
        return $this->canManageUserForRole(
            user: $user,
            subject: $subject,
            targetRole: $target,
        );
    }

    public function delete(
        User $user,
        Role $target,
    ): bool {
        if ($target->locked) {
            return false;
        }

        return $this->allowsActionOnRole(
            user: $user,
            target: $target,
            permission: RolePermissions::delete->value,
        );
    }

    public function restore(
        User $user,
        Role $target,
    ): bool {
        return $this->allowsActionOnRole(
            user: $user,
            target: $target,
            permission: RolePermissions::restore->value,
        );
    }

    public function forceDelete(
        User $user,
        Role $target,
    ): bool {
        if ($target->locked) {
            return false;
        }

        return $this->allowsActionOnRole(
            user: $user,
            target: $target,
            permission: RolePermissions::forceDelete->value,
        );
    }
    
    private function allowsActionOnRole(
        User $user,
        Role $target,
        string $permission,
        bool $checkHierarchy = true
    ): bool {
        $project = $this->resolveTargetProject($target);
        
        if ($target->isProject() && $project === null) return false;
        
        if (!$target->isSystem() && !$target->isProject()) return false;
        
        if (!$this->permissions->allows(
            user: $user,
            permission: $permission,
            project: $project,
        )) return false;
        
        if (!$checkHierarchy) return true;
        
        return $this->canManageRole(
            user: $user,
            target: $target,
            project: $project,
        );
    }
    
    private function resolveTargetProject(Role $target): ?Project {
        if ($target->isSystem()) return null;
        
        if (!$target->isProject()) return null;
        
        return $target->project;
    }
    
    private function hasRole(User $user, Role $role): bool {
        return $user->roles()
            ->whereKey($role->getKey())
            ->exists();
    }
    
    private function canManageRole(
        User $user,
        Role $target,
        ?Project $project,
    ): bool {
        if ($target->level === null) return false;
        
        if ($target->isSystem()) $actorLevel = $this->hierarchy->systemLevel($user);
        else {
            if ($project === null) return false;
            
            $actorLevel = $this->hierarchy->effectiveLevel($user, $project);
        }
        
        if ($actorLevel === null) return false;
        
        return $actorLevel < (int) $target->level;
    }
    
    private function canManageUserForRole(
        User $user,
        User $subject,
        Role $targetRole,
    ): bool {
        if ($targetRole->isSystem()) {
            $actorLevel = $this->hierarchy->systemLevel($user);
            $subjectLevel = $this->hierarchy->systemLevel($subject);
            
            if ($actorLevel === null) return false;
            if ($subjectLevel === null) return true;
            
            return $actorLevel < $subjectLevel;
        }
        
        if (!$targetRole->isProject()) return false;
        
        $project = $targetRole->project;
        
        if ($project === null) return false;
        
        $actorLevel = $this->hierarchy->effectiveLevel($user, $project);
        $subjectLevel = $this->hierarchy->effectiveLevel($subject, $project);
        
        if ($actorLevel === null) return false;
        if ($subjectLevel === null) return true;
        
        return $actorLevel < $subjectLevel;
    }
}
