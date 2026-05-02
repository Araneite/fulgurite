<?php

namespace App\Http\Requests;

use App\Exceptions\ValidationDataException;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class UserPasswordResetRequest extends FormRequest
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
            "email"=> "required|string|email|exists:fg_users,email",
            "token"=> "required|string",
            "password"=> "required|string|min:6|confirmed",
        ];
    }
    
    public function failedValidation(Validator $validator)
    {
        throw new ValidationDataException(
            trans('internal.errors.validation.message'),
            [
                "description"=> trans('internal.errors.validation.description'),
                "errors"=>$validator->errors(),
            ]
        );
    }
    
    public function withValidator(Validator $validator) {
        $validator->after(function ($validator) {
            if (!$this->filled("email") || !$this->filled("password")) {
                return;
            }
            
            $user = User::query()
                ->where("email", $this->input("email"))
                ->first();
            
            if (!$user) {
                return;
            }
            
            if (Hash::check($this->input("password"), $user->password)) {
                $validator->errors()->add(
                    "password", trans('internal.errors.users.reused_password.message'
                ));
            }
        });
    }
    
    public function resetPasswordData(): array {
        return [
            "email"=> $this->validated("email"),
            "token"=> $this->validated("token"),
            "password"=> $this->validated("password"),
            "password_confirmation"=> $this->validated("password_confirmation"),
        ];
    }
}
