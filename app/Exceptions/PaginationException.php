<?php

namespace App\Exceptions;

use Exception;

class PaginationException extends Exception
{
    protected $code = 404;
    protected $message = 'Page not found';
    protected $errors = null;
    
    public function __construct($message = null, $errors = null) {
        if ($message) $this->message = $message;
        if ($errors) $this->errors = $errors;
        
        return parent::__construct($this->message, $this->code);
    }
    
    public function report() {
        //
    }
    
    public function render($request) {
        return response()->json([
            "data"=> [],
            "success"=> false,
            "code"=> $this->code,
            "message"=> $this->message,
            "errors"=> $this->errors
        ], $this->code);
    }
}
