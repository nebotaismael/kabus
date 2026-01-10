<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;
use URL;

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
        // Fix for MySQL key length issue on older versions
        Schema::defaultStringLength(191);
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Paginator::defaultView('components.pagination');
        Paginator::defaultSimpleView('components.pagination');

        // Set Carbon locale to English
        Carbon::setLocale('en');
    }
}
