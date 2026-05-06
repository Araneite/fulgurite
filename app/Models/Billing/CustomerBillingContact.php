<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    "name", "email", "phone_number", "phone_extension", 
    "role", "is_primary", "receives_invoices", "receives_payment_reminders", 
    "language", "metadata", "customer_id", "created_at", "updated_at", "deleted_at"
])]
class CustomerBillingContact extends Model
{
    use softDeletes;
    
    protected $table = 'fg_customer_billing_contacts';
    
    public function casts(): array {
        return array(
            "name"=> "string",
            "email"=> "string",
            "phone_number"=> "integer",
            "phone_extension"=> "integer",
            "role"=> "string",
            "is_primary"=> "boolean",
            "receives_invoices"=> "boolean",
            "receives_payment_reminders"=> "boolean",
            "language"=> "string",
            "metadata"=> "array",
            "client_id"=> "integer",
            "created_at"=> "datetime",
            "updated_at"=> "datetime",
            "deleted_at"=> "datetime"
        );
    }
    
    public function customer(): HasOne {
        return $this->hasOne(Customer::class, "customer_id");
    }
}
