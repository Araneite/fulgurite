<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisabledAccountException extends Exception
{
    protected $code = 500;
    protected $message = 'This user account is disabled.';
    protected $errors = null;
    
    public function __construct($message = null, $errors = null) {
        if ($message) $this->message = $message;
        if ($errors) $this->errors = $errors;
        
        parent::__construct($this->message, $this->code);
    }
    
    public function report() {
        //
    }
    
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            "data" => [],
            "success" => false,
            "code"=> $this->code,
            "message"=> $this->message,
            "errors"=> $this->errors
        ]);
    }
    
}
