<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
