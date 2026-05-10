<?php

namespace Plugins\CoreReadModels\Models\Billing;

use App\Models\Billing\CustomerBillingContact;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Plugins\CoreReadModels\Models\ReadOnlyModel;

class Billing extends ReadOnlyModel
{
	protected $table = "fg_customers";
    protected $guarded = [];
    
    public function casts(): array {
        return [
            "metadata"=> "array",
            "created_at" => "datetime",
            "updated_at" => "datetime",
            "deleted_at" => "datetime",
        ];
    }
    
    public function billingProfile(): HasOne {
        return $this->hasOne(CustomerBillingProfile::class, "customer_id");
    }
    
    public function billingContact(): HasMany {
        return $this->hasMany(CustomerBillingContact::class, "customer_id");
    }
}
