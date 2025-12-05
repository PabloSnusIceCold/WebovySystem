<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // You can place route-related service registrations here if needed.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /** @var Router $router */
        $router = $this->app['router'];

        // Explicitly register the 'admin' middleware alias so it is available to the router.
        $router->aliasMiddleware('admin', \App\Http\Middleware\AdminMiddleware::class);
    }
}

