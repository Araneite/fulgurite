<?php

namespace App\Http\Requests;

use App\Exceptions\ValidationDataException;
use App\Http\Resources\API\Internal\BaseResource;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserUpdateRequest extends FormRequest
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
            "username"=> "sometimes|string|max:25|unique:fg_users,username",
            "role_id"=> "sometimes|integer|exists:fg_roles,id",
            "first_name"=> "sometimes|string|max:50",
            "last_name"=> "sometimes|string|max:50",
            "email"=> "sometimes|string|email|max:255|unique:fg_users,email",
            "phone"=> "sometimes|integer|min:9",
            "phone_ext"=> "sometimes|integer|min:1",
            "job_title"=> "sometimes|string|max:50",
            "preferred_locale"=> "sometimes|string",
            "preferred_timezone"=> "sometimes|string",
            "preferred_start_page"=> "sometimes|string",
            "force_actions"=> "sometimes|array",
            "expire_at"=> "sometimes|date|nullable",
            "admin_notes"=> "sometimes|string|max:300|nullable",
        ];
    }
    
    public function failedValidation(Validator $validator)
    {
        throw new ValidationDataException(
            trans("internal.errors.validation.message"), [
                "description"=> trans("internal.errors.validation.description"),
                "errors"=>$validator->errors(),
            ]
        );
    }
    
    
    /**
     * @return User|null
     */
    public function getTargetUser(): ?User {
        $userReq = $this->route("user");
        
        if (!$userReq) {
            return null;
        }
        
        return is_numeric($userReq) 
            ? User::where("id", $userReq)->first()
            : User::where("username", $userReq)->first();
    }
    
    public function userData(): array {
        return collect($this->validated())->only([
            "username", "email", "admin_notes",
            "expire_at", "active"
        ])->toArray();
    }
    
    public function userContactData(): array {
        return collect($this->validated())->only([
            "first_name", "last_name", "phone",
            "phone_extension", "job_title"
        
        ])->toArray();
    }
    
    public function userSettingData(): array {
        return collect($this->validated())->only([
            "preferred_locale", "preferred_timezone", 
            "preferred_start_page", "force_actions_json"
        ])->toArray();
    }
}
