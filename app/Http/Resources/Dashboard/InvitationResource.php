<?php

namespace App\Http\Resources\Dashboard;

use App\Traits\FormatDateForLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
{
    use FormatDateForLocale;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=> $this->id,
            'email'=> $this->email,
            'username'=> $this->username,
            'status'=> $this->status,
            'status_label'=> trans("resources/invitations.status.{$this->status}"),
            
            'expires_at'=> $this->formatDateForLocale($this->expires_at),
            'accepted_at'=> $this->formatDateForLocale($this->accepted_at),
            'revoked_at'=> $this->formatDateForLocale($this->revoked_at),
            
            'created_at'=> $this->formatDateForLocale($this->created_at),
            
            'invited_by'=> $this->whenLoaded('inviter', function () {
                return $this->inviter;
            }),
            
            'deleted_at'=> $this->deleted_at,
            'is_deleted'=> $this->trashed(),
        ];
    }
}
