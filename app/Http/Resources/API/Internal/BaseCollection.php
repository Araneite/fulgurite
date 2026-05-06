<?php

namespace App\Http\Resources\API\Internal;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class BaseCollection extends ResourceCollection
{
    private int $code = 200;
    private ?string $message = null;
    private bool $success = false;
    
    public function toArray(Request $request): array
    {
        $resourceClass = $this->collects ?? BaseResource::class;
        
        return [
            'list' => $this->collection
                ->map(fn ($item) => (new $resourceClass($item))->toArray($request))
                ->values(),
        ];
    }
    
    public function with(Request $request): array
    {
        return [
            'success' => $this->getSuccess(),
            'code' => $this->getCode(),
            'message' => $this->getMessage(),
            'meta' => $this->meta(),
            'links' => $this->links(),
        ];
    }
    
    public function withResponse(Request $request, JsonResponse $response): void
    {
        $response->setStatusCode($this->getCode());
    }
    
    public function paginationInformation($request, $paginated, $default): array
    {
        return [];
    }
    
    private function meta(): array
    {
        if ($this->resource instanceof LengthAwarePaginator) {
            return [
                'current_page' => $this->resource->currentPage(),
                'last_page' => $this->resource->lastPage(),
                'per_page' => $this->resource->perPage(),
                'total' => $this->resource->total(),
                'from' => $this->resource->firstItem(),
                'to' => $this->resource->lastItem(),
                'is_paginated' => true,
            ];
        }
        
        $total = $this->collection->count();
        
        return [
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => $total,
            'total' => $total,
            'from' => $total > 0 ? 1 : null,
            'to' => $total > 0 ? $total : null,
            'is_paginated' => false,
        ];
    }
    
    private function links(): array
    {
        if ($this->resource instanceof LengthAwarePaginator) {
            return [
                'first' => $this->resource->url(1),
                'last' => $this->resource->url($this->resource->lastPage()),
                'prev' => $this->resource->previousPageUrl(),
                'next' => $this->resource->nextPageUrl(),
            ];
        }
        
        return [
            'first' => null,
            'last' => null,
            'prev' => null,
            'next' => null,
        ];
    }
    
    public function success(): self
    {
        $this->success = true;
        
        return $this;
    }
    
    public function setCode(int $code): self
    {
        $this->code = $code;
        $this->success = $code >= 200 && $code < 300;
        
        return $this;
    }
    
    public function setMessage(?string $message): self
    {
        $this->message = $message;
        
        return $this;
    }
    
    public function getCode(): int
    {
        return $this->code;
    }
    
    public function getMessage(): ?string
    {
        return $this->message;
    }
    
    public function getSuccess(): bool
    {
        return $this->success;
    }
}
