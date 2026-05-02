<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ValidationDataException extends Exception
{
    protected $code = 422;
    protected $message = "Data validation failed.";
    protected $errors = null;
        
    public function __construct($message = null, $errors = null) {
        if ($message) $this->message = $message;
        if ($errors) $this->errors = $errors;
        
        parent::__construct($this->message, $this->code);
    }
    
    
    
    /**
     * Report the exception.
     */
    public function report(): void
    {
        //
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            "data"=> [],
            "success"=> false,
            "code"=> $this->code,
            "message"=> $this->message,
            "errors"=> $this->errors
        ]);
    }
}
