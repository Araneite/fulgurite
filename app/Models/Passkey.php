<?php

namespace App\Models;

use Laravel\Passkeys\Passkey as BasePasskey;

class Passkey extends BasePasskey
{
    protected $table = 'fg_passkeys';
}
