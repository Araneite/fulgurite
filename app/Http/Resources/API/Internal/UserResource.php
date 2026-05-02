<?php

namespace App\Http\Resources\API\Internal;

use Illuminate\Http\Request;
class UserResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array(
            "id"=> $this->id,
            "username"=> $this->username,
            "email"=> $this->email,
            /*"contact"=> array(
                "email"=> $this->email,
                "phone"=> ["extension"=> $this->ext, "number"=> $this->phone],
            ),*/
            "role"=> $this->whenLoaded('role', function () {
                return [
                    "id" => $this->role->id,
                    "name" => $this->role->name,
                    "permissions" => $this->role->permissions,
                    "scope" => $this->role->scope,
                ];
            }, null),
            "contact"=> $this->whenLoaded('contact', function () {
                return $this->contact;
            }, null),
            "settings"=> $this->whenLoaded('settings', fn () => $this->settings),
            "permissions" => $this->whenLoaded('permission', fn () => $this->permission?->permissions),
            "admin_notes"=> $this->admin_notes,
            "suspension"=> ["reason"=> $this->suspension_reason, "until"=> $this->suspended_until],
        );
    }
}
