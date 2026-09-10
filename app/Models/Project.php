<?php

namespace App\Models;

use App\Models\Billing\Customer;
use Attribute;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'slug', 
    'description',
    'status_page_enabled',
    'customer_id',
    'created_at',
    'updated_at',
    'deleted_at',
    'created_by',
    'updated_by',
    'deleted_by'
])]
class Project extends Model
{
    use Notifiable, softDeletes;
    
    protected $table = 'fg_projects';
    
    protected function casts(): array {
        return [
            "name"=> "string",
            'slug'=> "string",
            'description'=> "string",
            "status_page_enabled" => "boolean",
            "customer_id" => "integer",
            "created_at" => "datetime",
            "updated_at" => "datetime",
            'deleted_at' => "datetime",
            'created_by' => "integer",
            'updated_by' => "integer",
            'deleted_by' => "integer"
        ];
    }
    
    public function customer(): BelongsTo {
        return $this->belongsTo(Customer::class, "customer_id");
    }
}
