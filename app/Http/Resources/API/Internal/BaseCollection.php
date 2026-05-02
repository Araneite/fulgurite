<?php

namespace App\Http\Resources\API\Internal;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

class BaseCollection extends ResourceCollection
{
    private int $code;
    private string|null $message = null;
    private bool $success;
    
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array {
        $items = $this->collection->map(function($item) use ($request) {
            $resourceClass = $this->collects ?? BaseResource::class;
            return (new $resourceClass($item))->toArray($request);
        });
        
        $data = [
            "list" => $items,
        ];
        
        if ($this->resource instanceof AbstractPaginator) {
            $data["meta"] = [
                "current_page" => $this->resource->currentPage(),
                "last_page" => $this->resource->lastPage(),
                "per_page" => $this->resource->perPage(),
                "total" => $this->resource->total(),
                "from" => $this->resource->firstItem(),
                "to" => $this->resource->lastItem(),
            ];
            
            $data["links"] = [
                "first"=> $this->resource->url(1),
                "last"=> $this->resource->url($this->resource->lastPage()),
                "prev"=> $this->resource->previousPageUrl(),
                "next"=> $this->resource->nextPageUrl(),
            ];
        } else {
            $date["meta"]= [
                "total" => $this->collection->count(),
            ];
        }
        
        return $data;
    }
    
    // Define the response status according to status code
    public function withResponse(Request $request, JsonResponse $response) :void
    {
        $response->setStatusCode($this->getCode());
    }
    
    public function with(Request $request) :array {
        return array(
            "success"=> $this->getSuccess(),
            "code"=> $this->getCode(),
            "message"=> $this->getMessage()
        );
    }
    
    
    // Set the response status to success
    public function success(): BaseCollection
    {
        $this->success = true;
        return $this;
    }
    
    public function setCode($code): BaseCollection {
        $this->code = $code;
        return $this;
    }
    public function setMessage($message): BaseCollection {
        $this->message = $message;
        return $this;
    }
    
    public function getCode(): int {
        return $this->code;
    }
    public function getMessage(): string|null {
        return $this->message;
    }
    public function getSuccess(): bool {
        return $this->success;
    }
}
