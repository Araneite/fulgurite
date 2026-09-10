<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user) {
        return $user->hasPermission('roles:view');
    }
    
    public function view(User $user, Role $model) {
        return $user->hasPermission('roles:view');
    }
    
    public function create(User $user) {
        return $user->hasPermission('roles:create');
    }
    
    public function update(User $user, Role $model) {
        return $user->hasPermission('roles:update');
    }
    
    public function delete(User $user, Role $model) {
        return $user->hasPermission('roles:delete');
    }
    
    public function restore(User $user, Role $model) {
        return $user->hasPermission('roles:restore');
    }
    
    public function forceDelete(User $user, Role $model) {
        return $user->hasPermission('roles:forceDelete');
    }
    
    public function assign(User $user, Role $model): bool {
        return $user->hasPermission('roles:assign', $model);
    }
}
