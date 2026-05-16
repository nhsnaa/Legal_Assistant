<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // FORCE the view service to exist if it's failing to boot
        if (!$this->app->bound('view')) {
            $this->app->register(\Illuminate\View\ViewServiceProvider::class);
        }
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Force the storage path for Blade templates
        if (isset($_SERVER['RENDER'])) {
            config(['view.compiled' => '/tmp/storage/framework/views']);
        }
    }
}
