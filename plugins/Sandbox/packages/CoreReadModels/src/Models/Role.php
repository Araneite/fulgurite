<?php

namespace Plugins\CoreReadModels\Models;

use Plugins\CoreReadModels\Models\ReadOnlyModel;

class Role extends ReadOnlyModel
{
	protected $table = "fg_roles";
    protected $guarded = [];
    
    public function casts(): array {
        return [
            "permissions"=> "array",
            "created_at" => "datetime",
            "updated_at" => "datetime",
        ];
    }
}
