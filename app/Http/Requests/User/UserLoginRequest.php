<?php

namespace App\Http\Requests\User;

use App\Exceptions\ValidationDataException;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserLoginRequest extends FormRequest
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
            "login"=> "required",
            "password"=> "required",
            "remember"=> "boolean"
        ];
    }
    
    public function authenticate(): User {
        
        $loginField = filter_var($this->input("login"), FILTER_VALIDATE_EMAIL) ? "email" : "username";
        
        if (!Auth::attempt([
            $loginField => $this->validated("login"),
            "password"=> $this->validated("password")            
        ], $this->boolean('remember'))) {
            throw ValidationException::withMessages([
                "auth"=> trans("forms/login.errors.failed")
            ]);
        }
        
        return Auth::user();
    }
    
    public function messages(): array {
        return [
            "email.required"=> trans("forms/login.errors.email_required"),
            "email.email"=> trans("forms/login.errors.email_invalid"),
            "password.required"=> trans("forms/login.errors.password_required"),
            "remember.boolean"=> trans("forms/login.errors.remember_boolean"),
        ];
    }
    
    public function attributes(): array {
        return [
            "email"=> trans("forms/login.fields.email"),
            "password"=> trans("forms/login.fields.password"),
        ];
    }
    
    public function credentials(): array {
        return $this->only(["email", "password"]);
    }
}
