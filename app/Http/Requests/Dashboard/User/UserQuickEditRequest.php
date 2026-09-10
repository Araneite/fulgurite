<?php

namespace App\Http\Requests\Dashboard\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserQuickEditRequest extends BaseUserRequest
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
        $userId = $this->route('user')?->id ?? $this->route('user');

        return [
            'username'=> [
                'required',
                'string',
                'max:255',
                Rule::unique('fg_users', 'username')->ignore($userId),
            ],
            'email'=> [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('fg_users', 'email')->ignore($userId),
            ],
            'job_title'=> 'nullable|string|max:255',
            'phone.extension'=> 'nullable|string|max:10',
            'phone.number'=> 'nullable|string|max:10',
            'admin_notes'=> 'nullable|string',
        ];
    }
    
    public function userData(): array {
        return collect($this->validated())
            ->only(['username', 'email', 'admin_notes'])
            ->toArray();
    }
    public function contactData(): array {
        $validated = $this->validated();
        
        return [
            'job_title'=> $this->nullableString($validated['job_title'] ?? null),
            'phone_extension'=> $this->nullableString($validated['phone']['extension'] ?? null),
            'phone'=> $this->nullableString($validated['phone']['number'] ?? null),
        ];
    }
}
