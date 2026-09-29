<?php

namespace App\Policies;

use App\Enums\Project\ProjectPermissions;
use App\Models\Project;
use App\Models\User;
use App\Services\Authorization\PermissionChecker;

class ProjectPolicy
{
    public function __construct(
      private readonly PermissionChecker $permissions,  
    ) {}
    
    public function viewAny(User $user): bool {
        return $this->allowsSystemAction(
            user: $user,
            permission: ProjectPermissions::view->value,
        );
    }
    
    public function view(User $user, Project $project): bool {
        return $this->allowsProjectAction(
            user: $user,
            project: $project,
            permission: ProjectPermissions::view->value,
        );
    }
    
    public function create(User $user): bool {
        return $this->allowsSystemAction(
            user: $user,
            permission: ProjectPermissions::create->value,
        );
    }
    
    public function update(User $user, Project $project): bool {
        return $this->allowsProjectAction(
            user: $user,
            project: $project,
            permission: ProjectPermissions::update->value,
        );
    }
    
    public function manage(User $user, Project $project): bool {
        return $this->allowsProjectAction(
            user: $user,
            project: $project,
            permission: ProjectPermissions::manage->value,
        );
    }
    
    public function delete(User $user, Project $project): bool {
        return $this->allowsSystemAction(
            user: $user,
            permission: ProjectPermissions::delete->value,
        );
    }
    
    public function restore(User $user, Project $project): bool {
        return $this->allowsSystemAction(
            user: $user,
            permission: ProjectPermissions::restore->value,
        );
    }
    
    public function forceDelete(User $user, Project $project): bool {
        return $this->allowsSystemAction(
            user: $user,
            permission: ProjectPermissions::forceDelete->value,
        );
    }
    
    private function allowsSystemAction(User $user, string $permission): bool {
        return $this->permissions->allows(user: $user, permission: $permission);
    }
    
    private function allowsProjectAction(User $user, Project $project, string $permission): bool {
        return $this->permissions->allows(user: $user, permission: $permission, project: $project);
    }
}
