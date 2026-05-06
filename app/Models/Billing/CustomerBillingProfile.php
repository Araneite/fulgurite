<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    "company_name", "billing_email", "address_line1",
    "address_line2", "postal_code", "city", "state",
    "country", "currency", "language", "payment_terms",
    "billing_reference", "reverse_charge_vat", "external_billing_provider",
    "external_billing_id", "metadata", "customer_id",
    "created_at", "updated_at", "deleted_at"
])]
class CustomerBillingProfile extends Model
{
    use softDeletes;
    
    protected $table = 'fg_customer_billing_profiles';
    
    public function casts(): array {
        return array(
            "company_name" => "string",
            "billing_email" => "string",
            "address_line1" => "string",
            "address_line2" => "string",
            "postal_code" => "integer",
            "city" => "string",
            "state" => "string",
            "country" => "string",
            "currency" => "string",
            "language" => "string",
            "payment_terms" => "string",
            "billing_reference" => "string",
            "reverse_charge_vat" => "boolean",
            "external_billing_provider" => "string",
            "external_billing_id" => "string",
            "metadata" => "array",
            "customer_id" => "integer",
            "created_at" => "datetime",
            "updated_at" => "datetime",
            "deleted_at" => "datetime"
        );
    }
    
    public function contacts(): HasOne {
        return $this->hasOne(CustomerBillingContact::class, "client_id");
    }
    
    public function client(): HasOne {
        return $this->hasOne(Customer::class);
    }
}
