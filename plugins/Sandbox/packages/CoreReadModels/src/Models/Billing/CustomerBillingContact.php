<?php

namespace Plugins\CoreReadModels\Models\Billing;

use Plugins\CoreReadModels\Models\ReadOnlyModel;

class CustomerBillingContact extends ReadOnlyModel
{
    protected $table = 'fg_customer_billing_contacts';
    protected $guarded = [];
    
    protected function casts(): array
    {
        return [
            'customer_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
