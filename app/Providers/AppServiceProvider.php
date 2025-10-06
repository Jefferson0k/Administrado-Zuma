<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\TrackUserActivity;
use Illuminate\Support\ServiceProvider;
use App\Models\Investor;
use App\Observers\InvestorObserver;
use Illuminate\Support\Facades\Schema;

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

        // Registrar el observer para inversionistas (verificación automática de WhatsApp)
        Investor::observe(InvestorObserver::class);
        Schema::defaultStringLength(191);
    }
}