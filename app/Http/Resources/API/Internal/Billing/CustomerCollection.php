<?php

namespace App\Http\Resources\API\Internal\Billing;

use App\Http\Resources\API\Internal\BaseCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerCollection extends BaseCollection
{
    public $collects = CustomerResource::class;
}
