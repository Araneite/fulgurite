<?php

namespace Plugins\CoreReadModels\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Plugins\CoreReadModels\Models\ReadOnlyModel;

class User extends ReadOnlyModel
{
	protected $table = "fg_users";
    protected $guarded = [];
    
    public function casts(): array {
        return [
            "active"=> "boolean",
            "role_id"=> "integer",
            "contact_id"=> "integer",
            "user_settings_id"=> "integer",
            "last_login"=> "datetime",
            "expired_at"=> "datetime",
            "created_at"=> "datetime",
            "updated_at"=> "datetime",
        ];
    }
    
    public function role(): BelongsTo {
        return $this->belongsTo(Role::class, "role_id");
    }
}
