<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    protected $fillable = [
        'scope', 'ip_address', 'user_agent', 'success',
        'username', 'created_at'
    ];
    protected $table = 'fg_login_attempts';
    
    protected function casts() {
        return [
            'scope'=> 'string',
            'ip_address' => 'string',
            'user_agent' => 'string',
            'success' => 'boolean',
            'username' => 'string',
            'create_at' => 'datetime'
        ];
    }
    
    protected function ipAddress(): string {
        return Attribute::make(
            set: static fn (?string $ip) => $ip ?? 'Unknown',
        );
    }
    
    protected function userAgent(): string {
        return Attribute::make(
            set: static fn (?string $userAgent) => $userAgent ?? 'Unknown',
        );
    }
    
    
}
