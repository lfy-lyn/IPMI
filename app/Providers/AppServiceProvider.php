<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
        // Pagination aplikasi internal bergaya dashboard (tanpa varian dark:).
        // Halaman publik tidak memakai pagination.
        Paginator::defaultView('pagination.ipmi');
        Paginator::defaultSimpleView('pagination.ipmi-simple');
    }
}
