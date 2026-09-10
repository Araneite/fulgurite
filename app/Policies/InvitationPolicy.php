<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;

class InvitationPolicy
{
    public function viewAny(User $user): bool {
        return $user->hasPermission('invites:view');
    }    
    
    public function view(User $user, Invitation $model): bool {
        return $user->hasPermission('invites:view', $model);
    }
    
    public function renew(User $user, Invitation $model): bool {
        return $user->hasPermission('invites:renew', $model);
    }
    
    public function revoke(User $user, Invitation $model): bool {
        return $user->hasPermission('invites:revoke', $model);
    }
    
    public function reactive(User $user, Invitation $model): bool {
        return $user->hasPermission('invites:reactive', $model);
    }
    
    public function send(User $user): bool {
        return $user->hasPermission('invites:send');
    }
    
    public function update(User $user, Invitation $model): bool {
        return $user->hasPermission('invites:edit', $model);
    }
    
    public function delete(User $user, Invitation $model): bool {
        return $user->hasPermission('invites:delete', $model);
    }
    
    public function restore(User $user, Invitation $model): bool {
        return $user->hasPermission('invites:restore', $model);
    }
    
    public function forceDelete(User $user, Invitation $model): bool {
        return $user->hasPermission('invites:forceDelete', $model);
    }
}
