<?php

namespace App\Http\Requests\Dashboard\Invitation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InvitationRenewExpirationRequest extends FormRequest
{
    public int $min = 0;
    public int $max = 30;
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
            'renew_amount'=> "integer|min:{$this->min}|max:{$this->max}",
            'expiration_date'=> 'nullable|date|after:today',
        ];
    }
    
    public function messages(): array {
        return [
            'renew_amount.integer' => trans('resources/invitations.validation.renew_amount.integer'),
            'renew_amount.min' => trans('resources/invitations.validation.renew_amount.min', ['value'=> $this->min]),
            'renew_amount.max' => trans('resources/invitations.validation.renew_amount.max', ['value'=> $this->max]),
            'expiration_date.date' => trans('resources/invitations.validation.expiration_date.date'),
            'expiration_date.after' => trans('resources/invitations.validation.expiration_date.after'),
        ];
    }
    
    public function getData(): array {
        return collect($this->validated())
            ->filter(fn ($value) => $value !== '')
            ->all();
    }
}
