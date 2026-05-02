<?php

namespace App\Models;

use Attribute;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'preferred_locale',
    'preferred_timezone',
    'preferred_start_page',
    'repo_scope_mode',
    'repo_scope_json',
    'host_scope_mode',
    'host_scope_json',
    'force_actions_json',
    'primary_second_factor',
    'totp_enabled'
])]
class UserSetting extends Model
{
    use softDeletes;
    
    protected $table = 'fg_user_settings';
    
    protected function casts(): array {
        return [
            'preferred_locale' => "string",
            'preferred_timezone' => "string",
            'preferred_start_page' => "string",
            'repo_scope_mode' => "string",
            'repo_scope_json' => "array",
            'host_scope_mode' => "string",
            'host_scope_json' => "array",
            'force_actions_json' => "array",
            'primary_second_factor' => "string",
            'totp_enabled' => "boolean",
        ];
    }
    
    /*protected function preferred_local(): string {
        return Attribute::make(
            set: static fn(?string $value): ?string => $value,
        );
    }*/
}
