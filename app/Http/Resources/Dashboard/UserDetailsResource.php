<?php

namespace App\Http\Resources\Dashboard;

use App\Enums\User\Status;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDetailsResource extends JsonResource
{
    /**
     * Transform the resource into data suitable for the details drawer.
     *
     * Sensitive administration data is omitted entirely when the
     * authenticated user does not have the required permission.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $canViewSensitive = $request->user()->can(
            'viewSensitive',
            $this->resource,
        );

        $status = $this->resolveAccountStatus();
        
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            
            'status' => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            'active' => $this->active,
            'is_deleted' => $this->resource->trashed(),
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'contact' => $this->whenLoaded(
                'contact',
                fn (): ?array => $this->contact
                    ? [
                        'first_name' => $this->contact->first_name,
                        'last_name' => $this->contact->last_name,
                        'job_title' => $this->contact->job_title,
                        'phone' => [
                            'number' => $this->contact->phone,
                            'extension' => $this->contact->phone_extension,
                        ],
                    ]
                    : null,
            ),

            'sensitive' => $this->when(
                $canViewSensitive,
                fn (): array => [
                    'roles' => $this->roles
                        ->map(fn ($role): array => [
                            'id' => $role->id,
                            'name' => $role->name,
                        ])
                        ->values(),

                    'last_login' => $this->last_login,
                    'expire_at' => $this->expire_at,
                    'suspended_until' => $this->suspended_until,
                    'suspension_reason' => $this->suspension_reason,
                    'admin_notes' => $this->admin_notes,
                ],
            ),
            
            'permissions' => [
                'view_sensitive' => $canViewSensitive,
                'update' => $request->user()->can('update', $this->resource),
            ],
        ];
    }

    /**
     * Resolve the current functional status of the account.
     *
     * @return Status
     */
    private function resolveAccountStatus(): Status
    {
        if ($this->resource->trashed()) {
            return Status::trashed;
        }

        if ($this->suspended_until?->isFuture()) {
            return Status::suspended;
        }

        if ($this->expire_at?->isPast()) {
            return Status::expired;
        }

        if (!$this->active) {
            return Status::locked;
        }

        return Status::active;
    }
}
