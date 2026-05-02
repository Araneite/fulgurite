<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
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
            "username"=> "required|string|max:25|unique:fg_users,username",
            "password"=> "required|string|min:6|confirmed",
            "role_id"=> "required|integer|exists:fg_roles,id",
            "first_name"=> "sometimes|string|max:50",
            "last_name"=> "string|max:50",
            "active"=> "boolean",
            "email"=> "required|string|email|max:255|unique:fg_users,email",
            "phone"=> "sometimes|integer|min:9",
            "phone_extension"=> "sometimes|integer|min:1",
            "job_title"=> "sometimes|string|max:50",
            "preferred_locale"=> "sometimes|string",
            "preferred_timezone"=> "sometimes|string",
            "preferred_start_page"=> "sometimes|string",
            "force_actions_json"=> "sometimes|array",
            "expire_at"=> "sometimes|date|nullable",
            "admin_notes"=> "sometimes|string|max:300|nullable",
        ];
    }
    
    public function userData(): array {
        return collect($this->validated())->only([
            "username", "email", "admin_notes",
            "expire_at", "active", "password",
            "role_id"
        ])->toArray();
    }
    
    public function userContactData(): array {
        return collect($this->validated())->only([
            "first_name", "last_name", "phone", 
            "phone_extension", "job_title"
        ])->toArray();
    }
    
    public function userSettingsData(): array {
        return collect($this->validated())->only([
            "preferred_locale", "preferred_timezone", 
            "preferred_start_page", "force_actions_json",
        ])->toArray();
    }
}
