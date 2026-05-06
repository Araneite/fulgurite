<?php

namespace App\Exceptions;

use Exception;
use http\Env\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RequestNotConfirmedException extends Exception
{
    protected $code = 400;
    protected $message = 'The request was not confirmed.';
    protected $errors = null;
    
    public function __construct($message = null, $errors = null) {
        if ($message) $this->message = $message;
        if ($errors) $this->errors = $errors;
        
        parent::__construct($this->message, $this->code);
    }
    
    public function report() {
        //
    }
    
    public function render(Request $request): JsonResponse {
        return response()->json([
            "data"=> [],
            "success"=> false,
            "code"=> $this->code,
            "message"=> $this->message,
            "errors"=> $this->errors
        ]);
    }
}
