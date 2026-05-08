<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    "slug", "name", "version", 
    "provider", "enabled", "settings"
])]
class Plugin extends Model
{
    protected $table = 'fg_plugins';
    
    public function casts(): array {
        return array(
            "enabled"=> "boolean",
            "settings"=> "array"
        );
    }
}
