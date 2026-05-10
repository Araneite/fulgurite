<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('sandbox:status', function () {
    $this->info('Plugin sandbox is available.');
});
