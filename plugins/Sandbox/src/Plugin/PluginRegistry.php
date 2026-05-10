<?php

namespace Plugins\Sandbox\Plugin;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PluginRegistry
{
    public function enabled(): Collection
    {
        if (! Schema::hasTable('fg_plugins')) {
            return collect();
        }
        
        return DB::table('fg_plugins')
            ->where('enabled', true)
            ->orderBy('slug')
            ->get([
                'slug',
                'name',
                'version',
                'namespace',
                'provider',
                'settings',
            ]);
    }
}
