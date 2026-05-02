<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    "action_log_id", "user_id_snapshot", "actor_identifier",
    "actor_username_snapshot", "email_encrypted", "first_name_encrypted",
    "last_name_encrypted", "company_name_encrypted", "purpose",
    "retention_until", "created_by"
])]
class AuditIdentityLink extends Model
{
    protected $table = "fg_audit_identity_links";

    public function casts() {
        return [
            "email_encrypted"=> "encrypted",
            "first_name_encrypted"=> "encrypted",
            "last_name_encrypted"=> "encrypted",
            "company_name_encrypted"=> "encrypted",
            "retention_until"=> "datetime"
        ];
    }

    public function actionLog() {
        return $this->belongsTo(ActionLog::class, "action_log_id");
    }
}
