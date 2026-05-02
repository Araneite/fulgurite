<?php

namespace App\Http\Requests;

use App\Exceptions\ValidationDataException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class LogArchiveRequest extends FormRequest
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
            "date"=> "sometimes|date",
        ];
    }
    
    public function failedValidation(Validator $validator)
    {
        throw new ValidationDataException(
            trans('internal.errors.logs.invalid_date.message', [collect()->only("date")]), [
                "description" => trans('internal.errors.logs.invalid_date.description'),
                "errors" => $validator->errors()
            ]            
        );
    }
}
