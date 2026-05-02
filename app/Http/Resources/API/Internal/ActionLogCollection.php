<?php

namespace App\Http\Resources\API\Internal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ActionLogCollection extends BaseCollection
{
    public $collects = ActionLogResource::class;
    
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $total = $this->collection->count();
        
        return array(
            "list"=> $this->collection->transform(function ($log) use ($request) {
                return (new ActionLogResource($log))->toArray($request);
            }),
            "meta"=> [
                "total"=> $total,
            ]
        );
    }
}
