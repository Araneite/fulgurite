<?php

namespace App\Http\Requests\Dashboard\Invitation;

use App\Enums\ForcedActions;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserInvitationStoreRequest extends FormRequest
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
            'mode'=> ['required', Rule::in(['email', 'link'])],
            'email'=> ['required', 'email', 'max:255', 'unique:fg_users,email', 'unique:fg_user_invitations,email'],
            'username'=> ['nullable', 'string', 'max:255', 'unique:fg_users,username', 'unique:fg_user_invitations,username'],
            'roles'=> ['nullable', 'array'],
            'roles.*.value'=> ['required_with:roles', 'integer', 'exists:fg_roles,id'],
            'forced_actions'=> ['nullable', 'array'],
            'forced_actions.*'=> ['string', Rule::in(array_column(ForcedActions::cases(), 'value'))],
            'admin_notes'=> [ 'nullable', 'string'],
        ];
    }
    
    public function roleIds(): array {
        return collect($this->validated('roles', []))
            ->pluck('id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
    
    public function forcedActions(): array {
        return collect($this->validated('forced_actions', []))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
