<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'first_name',
    'last_name',
    'phone',
    'phone_extension',
    'job_title',
    'user_id',
])]
class Contact extends Model
{
    use softDeletes;

    protected $table = 'fg_contacts';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phone' => 'integer',
            'phone_extension' => 'integer',
            'user_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
