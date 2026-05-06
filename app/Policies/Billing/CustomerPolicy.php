<?php

namespace App\Policies\Billing;

use App\Models\Billing\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool {
        return $user->hasPermission('customers:view');
    }
    
    public function view(User $user): bool {
        return $user->hasPermission('customers:view');
    }
    
    public function create(User $user): bool {
        return $user->hasPermission('customers:create');
    }
    
    public function update(User $user): bool {
        return $user->hasPermission('customers:edit');
    }
    
    public function delete(User $user): bool {
        return $user->hasPermission('customers:delete');
    }
    
    public function restore(User $user): bool {
        return $user->hasPermission('customers:restore');
    }
    
    public function forceDelete(User $user): bool {
        return $user->hasPermission('customers:forceDelete');
    }
    
    public function export(User $user): bool {
        return $user->hasPermission('customers:export');
    }
    
    public function import(User $user): bool {
        return $user->hasPermission('customers:import');
    }
}
