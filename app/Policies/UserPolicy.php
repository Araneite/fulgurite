<?php

namespace App\Policies;

use App\Enums\Role\RoleScopes;
use App\Enums\User\UserPermissions;
use App\Models\Project;
use App\Models\User;
use App\Services\Authorization\PermissionChecker;
use App\Services\Authorization\RoleHierarchyChecker;

class UserPolicy
{
    public function __construct(
        private readonly PermissionChecker $permissions,
        private readonly RoleHierarchyChecker $hierarchy
    ) {}
    
    public function viewAny(User $user): bool {
        return $this->permissions->allows(
            user: $user,
            permission: UserPermissions::view->value
        );
    }

    public function view(User $user, User $target, ?Project $project = null): bool {
        return $this->allowsActionOnUser(
            user: $user,
            target: $target,
            permission: UserPermissions::view->value,
            project: $project,
            checkHierarchy: false
        );
    }
    
    public function viewSensitive(User $user, User $target, ?Project $project= null): bool {
        return $this->allowsActionOnUser(
            user: $user,
            target: $target,
            permission: UserPermissions::viewSensitive->value,
            project: $project,
            checkHierarchy: false
        );
    }
    
    public function create(User $user): bool {
        return $this->permissions->allows(
            user: $user,
            permission: UserPermissions::create->value
        );
    }
    
    public function update(User $user, User $target, ?Project $project = null): bool {
        return $this->allowsActionOnUser(
            user: $user,
            target: $target,
            permission: UserPermissions::update->value,
            project: $project,
        );
    }
    
    public function manage(User $user, User $target, ?Project $project = null): bool {
        return $this->allowsActionOnUser(
            user: $user,
            target: $target,
            permission: UserPermissions::manage->value,
            project: $project,
        );
    }
    
    public function delete(User $user, User $target, ?Project $project = null): bool {
        return $this->allowsActionOnUser(
            user: $user,
            target: $target,
            permission: UserPermissions::delete->value,
            project: $project,
        );
    }
    
    public function restore(User $user, User $target, ?Project $project = null): bool {
        return $this->allowsActionOnUser(
            user: $user,
            target: $target,
            permission: UserPermissions::restore->value,
            project: $project,
        );
    }
    
    public function forceDelete(User $user, User $target, ?Project $project = null): bool {
        return $this->allowsActionOnUser(
            user: $user,
            target: $target,
            permission: UserPermissions::forceDelete->value,
            project: $project,
        );
    }
    
    private function allowsActionOnUser(User $user, User $target, string $permission, ?Project $project = null, bool $checkHierarchy = true): bool {
        if (!$this->permissions->allows(
            user: $user,
            permission: $permission,
            project: $project,
        )) return false;
        
        if ($project !== null && !$this->belongsToProject($target, $project)) return false;
        
        if (!$checkHierarchy) return true;
        
        if ($project === null) return $this->canManageGlobally($user, $target);
        
        return $this->hierarchy->canManageUser($user, $target, $project);
    }
    
    private function belongsToProject(User $user, Project $project): bool {
        return $user->roles()
            ->where('fg_roles.scope', RoleScopes::Project->value)
            ->where('fg_roles.project_id', $project->getKey())
            ->exists();
    }
    
    private function canManageGlobally(User $user, User $target): bool {
        $actorLevel = $this->hierarchy->systemLevel($user);
        
        if ($actorLevel === null) return false;
        
        $targetLevel = $this->hierarchy->systemLevel($target);
        
        if ($targetLevel === null) return true;
        
        return $actorLevel < $targetLevel;
    }    
}
