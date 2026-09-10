<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=> $this->id,
            'username'=> $this->username,
            'email'=> $this->email,
            'masked_email'=> $this->maskEmail($this->email),
            'two_factor_methods'=> $this->settings?->enabledSecondFactorMethods() ?? [],
            'primary_second_factor'=> $this->settings?->primary_second_factor ?? null,
        ];
    }

    private function maskEmail(?string $email): string
    {
        if (! $email || ! str_contains($email, '@')) {
            return '';
        }

        [$local, $domain] = explode('@', $email, 2);

        $maskedLocal = mb_strlen($local) <= 2
            ? mb_substr($local, 0, 1) . '*'
            : mb_substr($local, 0, 1) . str_repeat('*', mb_strlen($local) - 2) . mb_substr($local, -1);

        return $maskedLocal . '@' . $domain;
    }
}
