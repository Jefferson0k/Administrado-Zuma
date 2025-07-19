<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\TrackUserActivity;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Aquí puedes registrar servicios si es necesario
    }

    public function boot(): void
    {
        // Forzar HTTPS en producción
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Agrupación de middlewares
        Route::middleware('auth')->group(function () {
            Route::middleware(TrackUserActivity::class)->group(function () {
                // Puedes registrar rutas o lógica adicional aquí
            });
        });
    }
}
