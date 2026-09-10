<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;

trait FiltersProjectScopedAccess
{
    protected function applyProjectScopedAccess(
        Builder $query,
        User $user,
        string $permission,
        string $projectRelation = "projects",
        string $projectKey = "fg_projects.id"
    ): Builder {
        if ($user->hasPermission($permission)) return $query;
        
        $authorizedProjectIds = $user->authorizedProjectIds($permission);
        
        if (empty($authorizedProjectIds)) return $query->whereRaw("1 = 0");
        
        return $query->whereHas($projectRelation, function (Builder $query) use ($authorizedProjectIds, $projectKey) {
            $query
                ->whereIn($projectKey, $authorizedProjectIds);
        });
    }
}
