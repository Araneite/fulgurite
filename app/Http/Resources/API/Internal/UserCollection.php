<?php

namespace App\Http\Resources\API\Internal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends BaseCollection
{
    public $collects = UserResource::class;
    
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $total = $this->collection->count();
        
        return array(
            "list"=> $this->collection->transform(function ($user) use ($request) {
                return (new UserResource($user))->toArray($request);
            }),
            "meta"=> [
                "total"=>$total,
            ]
        );
    }
}
