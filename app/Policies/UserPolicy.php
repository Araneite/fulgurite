<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool {
        return $user->hasPermission('users:view');
    }

    public function view(User $user, User $model): bool {
        return $user->hasPermission('users:view', $model);
    }
    
    public function viewSensitive(User $user, User $model): bool {
        return $user->hasPermission('users:view-sensitive', $model);
    }

    public function create(User $user): bool {
        return $user->hasPermission('users:create');
    }

    public function update(User $user, User $model): bool {
        return $user->hasPermission('users:edit', $model);
    }

    public function delete(User $user, User $model): bool {
        return $user->hasPermission('users:delete', $model);
    }

    public function restore(User $user, User $model): bool {
        return $user->hasPermission('users:restore');
    }

    public function forceDelete(User $user, User $model): bool {
        return $user->hasPermission('users:forceDelete');
    }
}
