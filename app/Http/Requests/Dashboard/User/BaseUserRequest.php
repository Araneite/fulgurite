<?php

namespace App\Http\Requests\Dashboard\User;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseUserRequest extends FormRequest
{
    public function authorize(): bool {
        return true;
    }
    
    public function attributes(): array {
        return [
            'username'=> trans('resources/users.fields.username'),
            'email'=> trans('resources/users.fields.email'),
            'first_name'=> trans('resources/users.fields.first_name'),
            'last_name'=> trans('resources/users.fields.last_name'),
            'job_title'=> trans('resources/users.fields.job_title'),
            'phone.extension'=> trans('resources/users.fields.phone.extension'),
            'phone.number'=> trans('resources/users.fields.phone.number'),
            'password'=> trans('resources/users.fields.password'),
            'password_confirmation'=> trans('resources/users.fields.password_confirmation'),
            'active' => trans('resources/users.fields.active'),
            'admin_notes' => trans('resources/users.fields.admin_notes'),
            'suspended_until' => trans('resources/users.fields.suspended_until'),
            'suspension_reason' => trans('resources/users.fields.suspension_reason'),
            'expire_at' => trans('resources/users.fields.expire_at'),
            'roles' => trans('resources/users.fields.roles'),
            'forced_actions' => trans('resources/users.fields.forced_actions'),
            'preferred_locale' => trans('resources/users.fields.preferred_locale'),
            'preferred_timezone' => trans('resources/users.fields.preferred_timezone'),
            'preferred_start_page' => trans('resources/users.fields.preferred_start_page'),
        ];
    }
    
    public function messages(): array {
        return [
            'username.unique'=> trans('resources/users.validation.username.unique'),
            'email.unique'=> trans('resources/users.validation.email.unique'),
            'password.confirmed'=> trans('resources/users.validation.password.confirmed'),
            'roles.*.id.exists' => trans('resources/users.validation.roles.exists'),
        ];
    }
    
    protected function nullableString(?string $value): ?string {
        if ($value === null) return null;
        
        $value = trim($value);
        
        return $value === '' ? null : $value;
    }
}
