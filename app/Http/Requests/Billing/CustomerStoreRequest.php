<?php

namespace App\Http\Requests\Billing;

use App\Exceptions\ValidationDataException;
use App\Models\Billing\Customer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class CustomerStoreRequest extends FormRequest
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
            // Customer information
            "name" => "required|string",
            "slug"=> "required|string|unique",
            "legal_name" => "required|string",
            "registration_number" => "sometimes|string",
            "vat_number" => "sometimes|string",
            "website" => "sometimes|string|url:http,https",
            "industry" => "sometimes|string",
            "status" => "sometimes|string",
            "customer_metadata" => "sometimes|array",
            
            // Customer billing profile
            "company_name" => "sometimes|string",
            "billing_email" => "required|string|email",
            "address_line1" => "required|string",
            "address_line2" => "sometimes|string",
            "postal_code" => "required|string",
            "city" => "required|string",
            "state" => "sometimes|string",
            "country" => "required|string",
            "language" => "sometimes|string",
            "payment_terms" => "sometimes|string",
            "billing_reference" => "sometimes|string",
            "reverse_charge_vat" => "boolean",
            "external_billing_provider"=> "sometimes|string",
            "external_billing_id" => "sometimes|string",
            "billing_profile_metadata" => "sometimes|array",
        ];
    }
    
    /**
     * @throws ValidationDataException
     */
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
    
    protected function prepareForValidation(): void {
        if (!$this->filled("name")) {
            return;
        }
        
        $name = trim($this->input("name"));
        
        $slug = Str::slug($name);
        
        $slugExists = Customer::query()
            ->where("slug", $slug)
            ->exists();
        
        if ($slugExists) {
            $slug = $this->makeUniqueSlug($slug);
        }
        
        $this->merge([
            'slug' => $slug,
        ]);
    }
    
    public function customerData() {
        return collect($this->validated())->only([
            "name", "slug", "legal_name", "registration_number", "status",
            "vat_number", "website", "industry", "customer_metadata",
        ]);
    }
    
    public function customerBillingProfileData() {
        return collect($this->validated())->only([
            "company_name", "billing_email", "address_line1", "address_line2",
            "postal_code", "city", "state", "country", "language",
            "payment_terms", "billing_reference", "reverse_charge_vat",
            "external_billing_provider", "external_billing_id",
            "billing_profile_metadata"
        ]);
    }
    
    
}
