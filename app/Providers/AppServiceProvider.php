<?php

namespace App\Providers;

use App\Http\ViewComposers\LayoutComposer;
use Illuminate\Support\Facades\View;
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
        // Shared layout data (navigation, flash message) is prepared by LayoutComposer,
        // so the layout views never read the session or the logged-in user themselves.
        View::composer('layouts.app', LayoutComposer::class.'@app');
        View::composer('layouts.navigation', LayoutComposer::class.'@navigation');
    }
}
