<?php

namespace App\Http\Resources\API\Internal;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\MessageBag;

class BaseResource extends JsonResource
{
    private int $code;
    private string|null $message = null;
    private array|null $errors = null;
    private bool $success = false;
    private array|null $details = null;

    private array $excludes = array();

    public static function error(): self {
        return new static([]);
    }
    
    public static function errorResponse(
        int $code,
        string $message,
        ?array $details = null,
        ?array $errors = null
    ): JsonResponse {
        $payload = [
            'data' => [],
            'success' => false,
            'code' => $code,
            'message' => $message,
        ];
        
        if (!empty($details)) {
            $payload['details'] = $details;
        }
        
        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }
        
        return response()->json($payload, $code);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /*if ($this->resource !== null)*/ return $this->resource;
//        return parent::toArray($request);
    }

    public function withResponse(Request $request, JsonResponse $response): void {
        $response->setStatusCode($this->getCode());
    }
    
    public function with(Request $request) {
        $arr = array(
            "success"=> $this->getSuccess(),
            "code"=> $this->getCode(),
            "message"=> $this->getMessage(),
        );
        
        if (!empty($this->getDetails())) $arr["details"] = $this->getDetails();
        if (!$this->getSuccess()) $arr["errors"] = $this->getErrors();
        return $arr;
    }
    
    /**
     * Define fields to exclude dynamically.
     *
     * @param array $fields
     * @return $this
     */
    public function except(array $fields = array()): static
    {
        $this->excludes = $fields;
        return $this;
    }
    
    /**
     * Exclude specified fields from data.
     *
     * @param array $data
     * @return array
     */
    protected function applyExcludes(array $data): array
    {
        foreach ($this->excludes as $field) {
            unset($data[$field]);
        }
        return $data;
    }
    
    //** Getters */
    public function setCode($code): self {
        $this->code = $code;
        $this->success = $code >= 200 && $code < 300;
        return $this;
    }
    public function setMessage($message): self {
        $this->message = $message;
        return $this;
    }
    public function setDetails(array $details): self {
        $this->details = $details;
        return $this;
    }
    public function setErrors($errors): self {
        if ($errors instanceof MessageBag) {
            $this->errors = $errors->toArray();
        } elseif (is_array($errors)) {
            $this->errors = $errors;
        } else {
            $this->errors = (array) $errors;
        }
        
        return $this;
    }
    
    //** Setters */
    public function success(): self {
        $this->success = true;
        return $this;
    }
    
    public function getCode(): int {
        return $this->code;
    }
    public function getMessage(): string|null {
        return $this->message;
    }
    public function getErrors(): mixed {
        return $this->errors;
    }
    public function getSuccess(): bool {
        return $this->success;
    }
    public function getDetails(): array|null {
        return $this->details;
    }

}
