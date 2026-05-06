<?php

namespace App\Models\Billing;

use App\Models\Contact;
use App\Traits\HasApiPagination;
use App\Traits\HasPermissions;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    "name", "slug", "legal_name",
    "registration_number", "vat_number",
    "website", "industry", "status", "metadata",
    "created_at", "updated_at", "deleted_at",
    'created_by', 'updated_by', "deleted_by"
])]
class Customer extends Model
{
    use softDeletes, HasApiPagination, HasPermissions;
    
    protected $table = 'fg_customers';
    
    public function casts():array {
        return array(
            "name"=> "string",
            "slug"=> "string",
            "legal_name"=> "string",
            "registration_number"=> "string",
            "vat_number"=> "string",
            "website"=> "string",
            "industry"=> "string",
            "status"=> "string",
            "metadata"=> "array",
            "created_at"=> "datetime",
            "updated_at"=> "datetime",
            "deleted_at"=> "datetime",
        );
    }
    
    public function billingProfile(): HasOne {
        return $this->hasOne(CustomerBillingProfile::class, "customer_id")->withTrashed();
    }
    
    public function billingContacts(): HasMany {
        return $this->hasMany(CustomerBillingContact::class, "customer_id");
    }
    
    public function delete(): ?bool {
        $user = auth()->user();
        
        $data = ['deleted_by' => $user->id];
        
        $this->update($data);
        
        return parent::delete();
    }
}
