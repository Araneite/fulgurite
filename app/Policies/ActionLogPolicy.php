<?php

namespace App\Policies;

use App\Models\ActionLog;
use App\Models\User;

class ActionLogPolicy
{
    public function viewAny(User $user): bool {
        return $user->hasPermission('logs:view');
    }

    public function view(User $user, ?ActionLog $log = null): bool {
        return $user->hasPermission('logs:view');
    }

    public function archive(User $user): bool {
        return $user->hasPermission('logs:archive');
    }
}
