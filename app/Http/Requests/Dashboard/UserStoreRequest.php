<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username'=> "required|unique:fg_users,username|string|max:255",
            'email'=> "required|string|email|max:255|unique:fg_users,email",
            'first_name'=> "nullable|string|max:255",
            'last_name'=> "nullable|string|max:255",
            'job_title'=> "nullable|string|max:255",
            'phone.extension'=> "nullable|string|max:10",
            'phone.number'=> "nullable|string|max:30",
            'password'=> "required|string|min:6|confirmed",
            'active'=> "boolean",
            'admin_notes'=> "nullable|string",
            'suspended_until'=> "nullable|date",
            'suspension_reason'=> "nullable|string",
            'expire_at'=> "nullable|date",
            'roles'=> "nullable|array",
            'forced_actions'=> "nullable|array",
        ];
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
        ];
    }
    
    public function messages(): array {
        return [
            'username.unique'=> trans('resources/users.validation.username.unique'),
            'email.unique'=> trans('resources/users.validation.email.unique'),
            'password.confirmed'=> trans('resources/users.validation.password.confirmed'),
            'roles.*.id.exists'=> trans('resources/users.validation.roles.exists'),
        ];
    }
    
    public function userData(): array {
        return collect($this->validated())->only([
            'username', 'email', 'password',
            'active', 'admin_notes', 'suspended_until',
            'suspension_reason', 'expire_at'
        ])->merge([
            'password_set_at'=> now()->toDateTimeString(),
            'created_by'=> $this->user()?->id
        ])->toArray();
    }
    
    public function contactData(): array {
        $validated = $this->validated();
        
        return [
            'first_name'=> $validated['first_name'] ?? null,
            'last_name'=> $validated['last_name'] ?? null,
            'job_title'=> $validated['job_title'] ?? null,
            'phone_extension'=> $validated['phone']['extension'] ?? null,
            'phone'=> $validated['phone']['number'] ?? null,
        ];
    }
    
    public function roleIds(): array {
        return collect($this->validated('roles', []))
            ->pluck('id')
            ->filter()
            ->values()
            ->all(); 
    }
}
