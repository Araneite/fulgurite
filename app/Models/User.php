<?php

namespace App\Models;

use App\Traits\HasApiPagination;
use App\Traits\HasPermissions;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'username',
    'email',
    'password',
    'role',
    'role_id',
    'admin_notes',
    'suspended_until',
    'suspension_reason',
    'expire_at',
    'active',
    'password_set_at',
    'last_login',
    'contact_id',
    'user_settings_id',
    'created_by',
    'updated_by',
    'deleted_by',
    'first_name',
    'last_name',
    'created_at',
    'updated_at',
])]
#[Hidden(['password'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens, HasPermissions, HasApiPagination;

    protected $table = 'fg_users';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'suspended_until' => 'datetime',
            'expire_at' => 'datetime',
            'active' => 'boolean',
            'password_set_at' => 'datetime',
            'last_login' => 'integer',
            'role_id' => 'integer',
            'contact_id' => 'integer',
            'user_settings_id' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
            'deleted_by' => 'integer',
            'password' => 'hashed',
        ];
    }

    protected function username(): Attribute
    {
        return Attribute::make(
            set: static fn (?string $value): ?string => $value !== null ? trim($value) : null,
        );
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            set: static fn (?string $value): ?string => $value !== null ? mb_strtolower(trim($value)) : null,
        );
    }
    

    public function creator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    public function contact(): HasOne
    {
        return $this->hasOne(Contact::class, 'user_id')->withTrashed();
    }
    
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
    
    public function roles(): BelongsToMany {
        return $this->belongsToMany(Role::class, 'fg_user_roles', 'user_id', 'role_id');
    }
    
    public function permission(): BelongsTo {
        return $this->belongsTo(Role::class, 'role_id');
    }
    
    public function settings(): BelongsTo {
        return $this->BelongsTo(UserSetting::class, 'user_settings_id')->withTrashed();
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(self::class, 'updated_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(self::class, 'deleted_by');
    }
    
    // Actions
    
    public function update(array $attributes = [], array $options = []): bool
    {
        if ($user = auth()->user()) $attributes["updated_by"] = $user->id;
        
        return parent::update($attributes, $options);
    }
    
    public function delete(): ?bool
    {
        $user = auth()->user();
        
        $data = ['deleted_by'=> $user->id];
        
        $this->update($data);
        
        return parent::delete();
    }
}
