<?php

namespace App\Http\Resources\API\Internal;

use Illuminate\Http\Request;

class RoleResource
{
    public function toArray(Request $request): array
    {
        return array(
            "id"=> $this->id,
            "name"=> $this->name,
            "permissions"=> $this->permissions,
            "created_at"=> $this->createdAt,
            "updated_at"=> $this->updatedAt
        );
    }
}
