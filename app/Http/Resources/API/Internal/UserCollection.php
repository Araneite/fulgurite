<?php

namespace App\Http\Resources\API\Internal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends BaseCollection
{
    public $collects = UserResource::class;
}
