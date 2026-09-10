<?php

namespace App\Traits;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait HasPermissions
{
    protected ?array $permissionDenial = null;
    
    public function getPermissions(): array {
        return $this->permissionRoles()
            ->flatMap(fn (Role $role) => $role->permissions ?? [])
            ->map(fn ($permission) =>   $this->normalizePermission($permission))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
    
    public function hasPermission(string $permission, ?Model $model = null): bool {
        $this->permissionDenial = null;
        
        $permission = $this->normalizePermission($permission);
        
        if (!$permission) return $this->denyPermission('invalid_permission');
        
        if (!$this->hasDirectPermission($permission)) {
            return $this->denyPermission('missing_permission', [
                'permission'=> $permission
            ]);
        };
        
        return $this->canActOnModel($model);
    }
    
    public function hasAnyPermission(array $permissions, ?Model $model = null): bool {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission, $model)) return true;
        }
        
        return false;
    }
    
    public function hasAllPermissions(array $permissions, ?Model $model = null): bool {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission, $model)) return false;
        }
        
        return true;
    }
    
    protected function hasDirectPermission(string $permission) : bool {
        $permissions = $this->getPermissions();
        
        if (in_array('*', $permissions, true)) return true;
        
        if (in_array($permission, $permissions, true)) return true;
        
        [$resource] = array_pad(explode(':', $permission, 2), 2, null);
        
        return $resource && in_array($resource . ':*', $permissions, true);
    }
    
    protected function canActOnModel(?Model $model = null): bool {
        if (!$model) return true;
        
        if ($model instanceof User) return $this->canActOnUser($model);
        
        if ($model instanceof Role) return $this->canActOnRole($model);
        
        return true;
    }
    
    protected function canActOnUser(User $target): bool {
        if ($this->id === $target->id) return true;
        
        $targetRoles = $target->relationLoaded('roles')
            ? $target->roles
            : $target->roles()->get();
        
        
        $targetRoles = $targetRoles->unique('id');
        
        if ($targetRoles->isEmpty()) return true;
        
        foreach ($targetRoles as $role) {
            if (!$this->canactOnRole($role)) {
                return $this->denyPermission('user_role_too_high', [
                    'target'=> $target->username,
                    'role'=> $role->name,
                ]);
            }
        }
        
        return true;
    }
    
    protected function canActOnRole(Role $targetRole): bool {
        $canAct = $this->permissionRoles()
            ->contains(fn (Role $actorRole)=> $this->roleCanManageRole($actorRole, $targetRole));
        
        if ($canAct) return true;
        
        return $this->denyPermission('role_too_high', [
            'role'=> $targetRole->name,
        ]);
    }
    
    protected function roleCanManageRole(Role $actorRole, Role $targetRole): bool {
        if ($targetRole->level === null || $actorRole->level === null) return false;
        
        if ($actorRole->project_id === null) return $targetRole->level >= $actorRole->level;
        
        return $targetRole->project_id === $actorRole->project_id
            && $targetRole->level >= $actorRole->level;
    }
    
    protected function permissionRoles() {
        $roles = $this->relationLoaded('roles')
            ? $this->roles
            : $this->roles()->get();
        
        return $roles->filter()
            ->unique('id')
            ->values();
    }
    
    protected function normalizePermission(string $permission): array|string|null {
        $permission = trim(strtolower($permission));
        
        if ($permission === '') return null;
        
        return preg_replace('/\s*:\s*/', ':', $permission);
    }
    
    public function permissionDenialMessage(string $actionTranslationKey, mixed $model = null): string {
        if (!$this->permissionDenial) {
            return trans("global/errors.unauthorized", [
                'action'=> strtolower(trans($actionTranslationKey)),
                'model'=> $this->permissionsModelName($model)
            ]);
        }
        
        return trans("global/errors.permissions.{$this->permissionDenial['key']}", [
            ...$this->permissionDenial['context'],
            'action'=> strtolower(trans($actionTranslationKey)),
            'model'=> $this->permissionModelName($model)
        ]);
    }
    
    public function permissionDenialApiMessage(string $actionTranslationKey, mixed $model = null): string {
        if (!$this->permissionDenial) {
            return trans('internal/errors.unauthorized.detail', [
                'end_sentence' => trans($actionTranslationKey),
            ]);
        }
        
        return trans("internal/errors.permissions.{$this->permissionDenial['denial']}", [
            ...$this->permissionDenial['context'],
            'action'=> strtolower(trans($actionTranslationKey)),
            'model'=> $this->permissionModelName($model), 
            'end_sentence'=> trans($actionTranslationKey),
        ]);
    }
    
    public function denyPermission(string $key, array $context = []): bool {
        $this->permissionDenial = [
            'key' => $key,
            'context' => $context,
        ];
        
        return false;
    }
    
    protected function permissionModelName(mixed $model): string {
        if (is_string($model)) {
            return class_basename($model);
        }
        
        if ($model instanceof Model) {
            return class_basename($model);
        }
        
        return 'ressource';
    }
}
