<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class UserResetPasswordException extends Exception
{
    protected $code = 400;
    protected $message = "There was a problem with the reset password";
    protected ?array $errors = null;
    
    public function __construct($message = null, $errors = null) {
        if ($message) $this->message = $message;
        if ($errors) $this->errors = $errors;
        
        parent::__construct($this->message, $this->code);
    }
    
    public function report() {
        //
    }
    
    public function render(): JsonResponse {
        return response()->json([
            "data"=> [],
            "success"=> false,
            "code"=> $this->code,
            "message"=> $this->message,
            "errors"=> $this->errors
        ]);
    }
}
