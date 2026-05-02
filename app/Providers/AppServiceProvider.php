<?php

namespace App\Providers;

use App\Events\ActionLogged;
use App\Listeners\HandleActionLogged;
use App\Listeners\UpdateLastLogin;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use App\Models\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blueprint::macro('userStamps', function () {
            /** @var Blueprint $this */
           $this->unsignedBigInteger('created_by');
           $this->unsignedBigInteger('updated_by')->nullable();
           $this->unsignedBigInteger('deleted_by')->nullable();

           $this->foreign("created_by")->references("id")->on("fg_users")->onDelete(null);
           $this->foreign("updated_by")->references("id")->on("fg_users")->onDelete(null);
           $this->foreign("deleted_by")->references("id")->on("fg_users")->onDelete(null);
        });

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Event::listen(Login::class, UpdateLastLogin::class);
        Event::listen(ActionLogged::class, HandleActionLogged::class);
    }
}
