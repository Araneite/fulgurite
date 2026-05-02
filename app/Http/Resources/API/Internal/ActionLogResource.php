<?php

namespace App\Http\Resources\API\Internal;

use Illuminate\Http\Request;

class ActionLogResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $this->whenLoaded('user', fn () => $this->user->settings?->preferred_locale, app()->getLocale());
        $target = is_string($this->target_type) || is_object($this->target_type) ? $this->target_type::find($this->target_id) : null;
        
        $login = request()->query("login") ?? "unknown";
        
        return array(
            "id"=> $this->id,
            "severity"=> $this->severity,
            "action"=> $this->action,
            "description"=> trans($this->description, ["user"=> $target?->username ?? "unknown", "updated_by"=> $this->whenLoaded('user', fn () => $this->user->username, 'unknown') ?? "unknown", "login"=> $login], $locale),
            "target"=> $this->whenLoaded('target', fn () => $target, null),
            "ip_address"=> $this->ip_address,
            "user_agent"=> $this->user_agent,
            "request"=> [
                "method"=> $this->method,
                "url"=> $this->url
            ],
            "user"=> $this->whenLoaded('user', fn () => $this->user, 'unknown'),
            "metadata"=> $this->metadata,
            "date"=> $this->created_at
        );
    }
}
