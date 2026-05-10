<?php

namespace Plugins\CoreReadModels\Models\Billing;

use Plugins\CoreReadModels\Models\ReadOnlyModel;

class CustomerBillingProfile extends ReadOnlyModel
{
    protected $table = 'fg_customer_billing_profiles';
    protected $guarded = [];
    
    protected function casts(): array
    {
        return [
            'billing_address' => 'array',
            'tax_metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
