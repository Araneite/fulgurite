<?php

namespace App\Exceptions;

use Exception;

class InvitationAcceptanceException extends Exception
{
    public function __construct(
        public readonly string $reason,
        string $message = '',
    ) {
        parent::__construct($message);
    }
    
    public static function notFound(): self {
        return new self('not_found');
    }
    
    public static function expired(): self {
        return new self('expired');
    }
    
    public static function accepted(): self {
        return new self('accepted');
    }
    
    public static function revoked(): self {
        return new self('revoked');
    }
}
