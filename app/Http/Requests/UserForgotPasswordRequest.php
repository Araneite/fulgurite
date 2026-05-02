<?php

namespace App\Http\Requests;

use App\Exceptions\ValidationDataException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UserForgotPasswordRequest extends FormRequest
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
            "email" => "required|email",
        ];
    }
    
    public function  failedValidation(Validator $validator)
    {
        throw new ValidationDataException(
            trans("internal.errors.validation.message"), [
                "description" => trans("internal.errors.validation.description"),
                "errors" => $validator->errors()
            ]
        );
    }
}
