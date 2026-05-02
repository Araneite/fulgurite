<?php

namespace App\Models;

use Attribute;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'permissions',
    'scope', 
    'created_at',
    'updated_at',
])]
class Role extends Model
{
    use Notifiable, softDeletes;
    
    protected $table = 'fg_roles';
    
    protected function casts(): array {
        return [
            "name"=> "string",
            "permissions" => "array",
            "scope" => "string",
            "created_at" => "datetime",
            "updated_at" => "datetime",
        ];
    }
}
