<?php

namespace App\Http\Requests\Billing;

use App\Exceptions\ValidationDataException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CustomerContactStoreRequest extends FormRequest
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
            "name"=> "required|string",
            "email"=> "required|email",
            "phone_number"=> "required|integer",
            "phone_extension"=> "required|integer",
            "role"=> "sometimes|string",
            "is_primary"=> "sometimes|boolean",
            "receives_invoices"=> "sometimes|boolean",
            "receives_payment_reminder"=> "sometimes|boolean",
            "language"=> "sometimes|string",
            "metadata"=> "sometimes|array"
        ];
    }
    
    public function failedValidation(Validator $validator)
    {
        throw new ValidationDataException(
            trans('internal/errors.validation.message'),
            [
                "validation_failed"=> trans('internal/errors.validation.detail'),
                "errors" => $validator->errors(),
            ]
        );
    }
    
    public function getContactData(): array {
        return collect($this->validated())->only([
            "name", "email", "phone_number", "phone_extension", "role", "is_primary",
            "receives_invoices", "receives_payment_reminder", "language", "metadata"
        ])->toArray();
    }
}
