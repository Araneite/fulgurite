<?php

namespace App\Http\Requests\Billing;

use App\Exceptions\ValidationDataException;
use App\Models\Billing\Customer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class CustomerUpdateRequest extends FormRequest
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
            "name"=> "sometimes|string",
            "slug"=> "sometimes|string",
            "legal_name"=> "sometimes|string",
            "registration_number"=> "sometimes|string",
            "vat_number"=> "sometimes|string",
            "website"=> "sometimes|string|url:http,https",
            "industry"=> "sometimes|string",
            "status"=> "sometimes|string",
            "customer_metadata"=> "sometimes|array",
            
            // Customer billing profile
            "company_name"=> "sometimes|string",
            "billing_email"=> "sometimes|string|email",
            "address_line1"=> "sometimes|string",
            "address_line2"=> "sometimes|string",
            "postal_code"=> "sometimes|string",
            "city"=> "sometimes|string",
            "state"=> "sometimes|string",
            "country"=> "sometimes|string",
            "language"=> "sometimes|string",
            "payment_terms"=> "sometimes|string",
            "billing_reference"=> "sometimes|string",
            "reverse_charge_vat"=> "sometimes|boolean",
            "external_billing_provider"=> "sometimes|string",
            "external_billing_id"=> "sometimes|string",
            "billing_profile_metadata"=> "sometimes|array",
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
                "validation_failed"=> trans('internal/errors.validation.description'),
                "errors" => $validator->errors(),
            ]
        );
    }
    
    protected function prepareForValidation(): void
    {
        if (! $this->filled('name')) {
            return;
        }
        
        $name = trim($this->input('name'));
        
        $customer = is_numeric($this->route('customer'))
            ? Customer::query()->where('id', $this->route('customer'))->first()
            : Customer::query()->where('slug', $this->route("customer"))->first();
        $oldName = $customer?->name;
        
        if ($oldName !== null && $name === $oldName) {
            return;
        }
        
        $slug = Str::slug($name);
        
        $slugExists = Customer::query()
            ->where('slug', $slug)
            ->when($customer, fn ($query) => $query->whereKeyNot($customer->getKey()))
            ->exists();
        
        if ($slugExists) {
            $slug = $this->makeUniqueSlug($slug, $customer?->getKey());
        }
        
        $this->merge([
            'name' => $name,
            'slug' => $slug,
        ]);
    }
    
    private function makeUniqueSlug(string $baseSlug, int|string|null $exceptId = null): string {
        $slug = $baseSlug;
        $counter = 2;
        
        while (
        Customer::query()
            ->where('slug', $slug)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }
        
        return $slug;
    }
    
    public function customerData(): array {
        return collect($this->validated())->only([
            "name", "slug", "legal_name", "registration_number",
            "vat_number", "website", "industry", "status", "customer_metadata",
        ])->toArray();
    }
    
    public function customerBillingProfileData(): array {
        return collect($this->validated())->only([
            "company_name", "billing_email", "address_line1", "address_line2",
            "postal_code", "city", "state", "country", "language",
            "payment_terms", "billing_reference", "reverse_charge_vat",
            "external_billing_provider", "external_billing_id",
            "billing_profile_metadata"
        ])->toArray();
    }
    
    /**
     * @return Customer|null
     */
    public function getTargetCustomer(): Customer|null {
        $customerReq = $this->route("customer");
        
        if (! $customerReq) {
            return null;
        }
        
        return is_numeric($customerReq)
            ? Customer::where("id", $customerReq)->first()
            : Customer::where('slug', $customerReq)->first();
    }
    
}
