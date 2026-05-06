<?php

namespace App\Http\Resources\API\Internal\Billing;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerBillingContactCollection extends ResourceCollection
{
    public $collects = CustomerBillingContactResource::class;
}
