<?php

namespace App\Http\Resources\Dashboard;

use App\Enums\ForcedActions;
use App\Traits\FormatDateForLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    use FormatDateForLocale;
    
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $canViewSensitive = $request->user()->can(
            'viewSensitive',
            $this->resource,
        );

        return [
            // Core user
            'id'=> $this->id,
            'username'=> $this->username,
            'email'=> $this->email,
            'updated_at'=> $this->formatDateForLocale($this->updated_at),
            
            // User profile
            'job_title'=> $this->whenLoaded('contact', fn () => $this->contact?->job_title),
            'last_name'=> $this->whenLoaded('contact', fn () => $this->contact?->last_name),
            'first_name'=> $this->whenLoaded('contact', fn () => $this->contact?->first_name),
            'phone'=> $this->whenLoaded(
                'contact',
                fn (): ?array => $this->contact
                    ? [
                        'extension'=> $this->contact->phone_extension,
                        'phone'=> $this->contact->phone,
                    ]
                    : null,
            ),
            
            // Roles
            'roles'=> $this->when(
                $canViewSensitive && $this->resource->relationLoaded('roles'),
                fn () => $this->roles
                    ->map(fn ($role): array => [
                        'id'=> $role->id,
                        'name'=> $role->name,
                    ])
                    ->values(),
            ),
            
            // Admin
            'expire_at'=> $this->when($canViewSensitive, $this->expire_at),
            'active'=> $this->active,
            'admin_notes'=> $this->when($canViewSensitive, $this->admin_notes),
            
            // settings
            'forced_actions'=> $this->when(
                $canViewSensitive && $this->resource->relationLoaded('settings'),
                fn () => $this->validForcedActions(),
            ),
            'preferred_locale'=> $this->when(
                $canViewSensitive && $this->resource->relationLoaded('settings'),
                fn () => $this->settings?->preferred_locale,
            ),
            'preferred_timezone'=> $this->when(
                $canViewSensitive && $this->resource->relationLoaded('settings'),
                fn () => $this->settings?->preferred_timezone,
            ),
            
            // Permissions
            'permissions'=> [
                'view'=> $request->user()->can('view', $this->resource),
                'view_sensitive'=> $canViewSensitive,
                'update'=> $request->user()->can('update', $this->resource),
                'delete'=> $request->user()->can('delete', $this->resource),
                'restore'=> $request->user()->can('restore', $this->resource),
                'force_delete'=> $request->user()->can('forceDelete', $this->resource),
            ],
            
            // Views
            'deleted_at'=> $this->deleted_at,
            'is_deleted'=> $this->trashed()
        ];
    }
    
    private function validForcedActions(): array {
        return collect($this->settings?->force_actions_json ?? [])
            ->filter(fn ($action)=> ForcedActions::tryFrom($action) !== null)
            ->values()
            ->all();
    }
}
