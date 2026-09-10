<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\RoleResource;
use App\Models\Role;
use App\Traits\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    use AuthorizesRequests;
    
    public function selectMenu(Request $request): JsonResponse {        
        $user = $this->authorizeUserAction($request, "viewAny", 'actions.list', Role::class);
        
        $validated = $request->validate([
            'search'=> "nullable|string"
        ]);
        
        $query = Role::query()->select(['id', 'name', 'level', 'project_id'])->with('project');
        $userRoles = $user->roles;
        
        if (key_exists( 'search', $validated) && $validated['search'] !== '') {
            $query->where('name', 'like', "%{$validated['search']}%");
        }
        
        $userRoles->map(function ($role) use ($query) {
            if ($role->project_id === null) {
                $query->where('level', '>=', $role->level);
            } else if ($role->project_id !== null) {
                $query->where('project_id', $role->project_id)
                    ->where('level', '>', $role->level);
            }
        });
        
        
        $roles = $query->orderBy('project_id')
            ->orderBy('level')
            ->get()
            ->filter(fn (ROle $role) => $request->user()->can('assign', $role))
            ->values();
            
        return response()->json(
            RoleResource::collection($roles)
        );
    }
}
