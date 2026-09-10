<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'email', 'username', 'payload',
    'expires_at', 'accepted_at', 'revoked_at',
    'accepted_user_id', 'invited_by', 'created_at',
    'updated_at', 'deleted_at', 'token_hash', 'token', 'status'
])]
class Invitation extends Model
{
    use softDeletes;
    
    protected $table = 'fg_user_invitations';
    
    protected function casts(): array {
        return [
            'email' => 'string',
            'username'=> 'string',
            'payload' => 'array',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'accepted_user_id' => 'integer',
            'invited_by' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'token_hash'=> 'string',
            'token'=> 'encrypted:string',
            'status'=> 'string',
        ];
    }
    
    public function inviter(): BelongsTo {
        return $this->belongsTo(User::class, 'invited_by');
    }
    
    public function accept(User $user): ?bool {
        return $this->update([
            'accepted_at' => now(),
            'status'=> 'accepted',
            'accepted_user_id'=> $user->id,
        ]);
    }
    
    public function revoke(User $user): ?bool {        
        return $this->update([
            'revoked_at'=> now(),
            'status'=> 'revoked',
        ]);
    }
}
