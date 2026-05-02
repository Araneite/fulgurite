<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id', 'user_role',
    'severity', 'action', 'description',
    'target_type', 'target_id',
    'ip_address', 'user_agent', 'url', 'method',
    'metadata', 'created_at'
])]
class ActionLog extends Model
{
    protected $table = 'fg_action_logs';
    
    public $timestamps = false;
    
    public function casts(): array {
        return [
            'user_id' => 'int',
            'target_id' => 'int',
            'metadata' => 'array',
            'created_at' => 'timestamp',
        ];
    }
    
    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function target() {
        return $this->morphTo();
    }
    
    public function getTranslateDescription(?string $locale = null): ?string {
        if (!$this->description) return null;
        return trans($this->description, $this->metadata ?? [], $locale);
    }

    public function auditIdentityLink() {
        return $this->hasOne(AuditIdentityLink::class, "action_log_id");
    }
    
}
