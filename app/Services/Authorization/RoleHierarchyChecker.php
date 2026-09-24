<?php

namespace App\Services\Authorization;

use App\Models\Project;
use App\models\User;
use Illuminate\Support\Collection;

final class RoleHierarchyChecker
{
    public function canManageUser(
        User $actor,
        User $target,
        Project $project
    ): bool {
        $actorLevel = $this->effectiveLevel($actor, $project);
        
        $targetLevel = $this->effectiveLevel($target, $project);
        
        if ($actorLevel === null || $targetLevel === null) return false;
        
        return $actorLevel < $targetLevel;
    }
    
    public function effectiveLevel(
        User $user,
        Project $project
    ): ?int {
        $roles = $this->roles($user);
        
        $systemLevel = $roles
            ->filter(fn ($role) => $role->isSystem())
            ->min('level');
        
        if ($systemLevel !== null) return (int) $systemLevel;
        
        $projectLevel = $roles
            ->filter(fn ($role)=>
                $role->isProject()
                && (int) $role->project_id === (int) $project->getKey()
            )
            ->min('level');
        
        return $projectLevel !== null
            ? (int) $projectLevel
            : null;
    }
    
    public function systemLevel(User $user): ?int {
        $level = $this->roles($user)
            ->filter(fn ($role) => $role->isSystem())
            ->min('level');
        
        return $level !== null
            ? (int) $level
            : null;
    }
    
    public function projectLevel(User $user, Project $project): ?int {
        $level = $this->roles($user)
            ->filter(fn ($role) => 
                $role->isProject()
                && (int) $role->project_id === (int) $project->getKey()
            )
            ->min('level');
        
        return $level !== null
            ? (int) $level
            : null;
    }
    
    private function roles(User $user): Collection {
        $user->loadMissing('roles');
        
        return $user->roles;
    }
}
