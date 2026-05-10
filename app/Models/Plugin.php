<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    "slug", "name", "version", 
    "path", "namespace", "provider", 
    "enabled", "settings", "installed_at",
])]
class Plugin extends Model
{
    protected $table = 'fg_plugins';
    
    public function casts(): array {
        return array(
            "enabled"=> "boolean",
            "settings"=> "array",
            "installed_at"=> "datetime",
        );
    }
    
    public function routes(): HasMany {
        return $this->hasMany(PluginRoute::class, 'plugin_id');
    }
}
