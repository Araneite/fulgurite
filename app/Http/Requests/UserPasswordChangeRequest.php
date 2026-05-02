<?php

namespace App\Http\Requests;

use App\Exceptions\ValidationDataException;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class UserPasswordChangeRequest extends FormRequest
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
            "password"=> "sometimes|string|min:6|confirmed",
        ];
    }
    
    public function failedValidation(Validator $validator)
    {
        throw new ValidationDataException(
            trans("internal.errors.users.reused_password.message"), [
                "description"=> trans("internal.errors.users.reused_password.description"),
                "errors"=>$validator->errors(),
            ]
        );
    }
    
    public function withValidator($validator): void {
        $validator->after(function ($validator) {
            $targetUser = $this->getTargetUser() ?? $this->user();
            
            if (!$targetUser || !$this->filled("password")) {
                return;
            }
            
            if (Hash::check($this->input('password'), $targetUser->password)) {
                $validator->errors()->add(
                    "password",
                    "password.is_same"    
                );
            }
        });
    }
    
    public function getTargetUser(): ?User {
        $userReq = $this->route("user");
        
        if (!$userReq) {
            return null;
        }
        
        return is_numeric($userReq)
            ? User::where("id", $userReq)->first()
            : User::where("username", $userReq)->first();
    }
    
    public function userResetPasswordData(): array {
        $data = collect($this->validated())->only([
            "password"
        ])->toArray();
        
        if (array_key_exists("password", $data)) {
//            $data["password"] = Hash::make($data["password"]);
            $data["password_set_at"] = now()->toDateTimeString();
        }
        
        return $data;
    }
}
