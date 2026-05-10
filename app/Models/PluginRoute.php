<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    "plugin_id", "method", "uri",
    "core_middleware", "enabled"
])]
class PluginRoute extends Model
{
	protected $table = "fg_plugin_routes";
    
    public function casts(): array {
        return [
            "core_middleware"=> "array",
            "enabled" => "boolean"
        ];
    }
    
    public function plugin(): BelongsTo {
        return $this->belongsTo(Plugin::class);
    }
}
