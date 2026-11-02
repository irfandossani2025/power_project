<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
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
        // The managed MariaDB server uses the legacy 767-byte index limit.
        // Keep indexed UTF-8 strings within that limit for reliable migrations.
        Schema::defaultStringLength(191);
    }
}
