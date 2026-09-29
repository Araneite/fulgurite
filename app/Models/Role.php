<?php

namespace App\Models;

use App\Enums\Role\RoleScopes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use LogicException;

#[Fillable([
    'name',
    'permissions',
    'scope',
    'level',
    'locked',
    'project_id',
    'created_at',
    'updated_at',
])]
class Role extends Model
{
    use HasFactory, Notifiable, softDeletes;
    
    protected $table = 'fg_roles';
    
    protected function casts(): array {
        return [
            "name"=> "string",
            "permissions" => "array",
            "scope" => RoleScopes::class,
            'level'=> 'integer',
            'locked'=> 'boolean',
            "project_id" => 'int',
            "created_at" => "datetime",
            "updated_at" => "datetime",
        ];
    }
    
    protected static function booted(): void {
        static::saving(function (Role $role) {
            if ($role->scope === RoleScopes::Project && $role->project_id === null) {
                throw new LogicException("A project-scoped role must have a project_id.");
            }
            
            if ($role->scope === RoleScopes::System && $role->project_id !== null) {
                throw new LogicException("A system role cannot have a project_id.");
            }
        });
    }
    
    // === Relations ===
    public function project(): BelongsTo {
        return $this->belongsTo(Project::class, "project_id");
    }
    
    // === Utils functions ===
    public function isSystem(): bool {
        return $this->scope === RoleScopes::System;
    }
    
    public function isProject(): bool {
        return $this->scope === RoleScopes::Project;
    }
}
