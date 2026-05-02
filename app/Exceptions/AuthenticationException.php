<?php

namespace App\Exceptions;

use Exception;

class AuthenticationException extends Exception
{
    protected $code = 403;
    protected $message = "Unauthenticated.";
    protected $errors = null;
    
    public function __construct($message = null, $errors = null) {
        if ($message) $this->message = $message;
        if ($errors) $this->errors = $errors;
        
        parent::__construct($this->message, $this->code);
    }
    
    public function getErrors(): mixed {
        return $this->errors;
    }
    
    public function render($request) {
        return response()->json([
            'data'=> [],
            'success'=> false,
            'code' => $this->code,
            'message' => $this->message,
            'errors' => $this->errors            
        ]);
    }
    
    /**
     * Report the exception.
     */
    public function report(): void
    {
        
    }
}
