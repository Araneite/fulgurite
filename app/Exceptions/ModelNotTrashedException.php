<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModelNotTrashedException extends BaseException
{
    protected $code = 400;
    protected $message = 'The requested model is not trashed.';
    protected ?array $errors = null;
    
    protected ?Model $model = null;
    protected ?string $requested = null;
    
    public function __construct($message = null, $errors = null) {
        if ($message) $this->message = $message;
        if ($errors) $this->errors = $errors;
        
        parent::__construct($this->message, $this->code);
    } 
    
    public function report() {
        //
    }
    
    public function setModel (Model $model): void{
        $this->model = $model;
    }
    public function setRequested(string $requested) {
        $this->requested = $requested;
    }
    
    public function getModel(): ?Model {
        return $this->model;
    }
    public function getRequested(): ?string {
        return $this->requested;
    }
    
    
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            "data"=> [],
            "success"=> false,
            "code"=> $this->code,
            "message"=> trans("internal/errors.not_trashed.detail", [
                "model"=> trans("internal/models.singular.user"),
                "requested"=> $this->getRequested(),
            ]),
            "errors"=> $this->errors
        ]);
    }
}
