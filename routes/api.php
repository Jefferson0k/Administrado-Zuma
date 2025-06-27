<?php

use App\Http\Controllers\Api\CreditSimulationController;
use App\Http\Controllers\Api\InvestmentController;
use App\Http\Controllers\Api\TipoCambioSbs;
use App\Http\Controllers\Panel\CalculadoraController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\CustomerSessionController;
use App\Http\Controllers\Auth\RegisteredCustomerController;
use App\Http\Controllers\Panel\CurrencyControllers;
use App\Http\Controllers\Panel\DeadlinesControllers;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Panel\PropertyControllers;
use App\Http\Controllers\Panel\InvestmentControllers;
use App\Http\Controllers\Panel\LoanSimulationController;

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS (sin login)
|--------------------------------------------------------------------------
*/

Route::post('/login', [CustomerSessionController::class, 'login']);
Route::post('/customers/register', [RegisteredCustomerController::class, 'store']);
Route::post('/email/verify/resend/{id}', [ProfileController::class, 'resendEmailVerification']);
Route::put('/email/verify/{id}/{hash}', [ProfileController::class, 'emailVerification']);

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (requieren login con Sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/customers/logout', [CustomerSessionController::class, 'logout']);

    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'profile']);
    });

    Route::prefix('property')->group(function () {
        Route::get('/', [PropertyControllers::class, 'index'])->name('property.index');
        Route::get('/active/sow', [PropertyControllers::class, 'indexSubastaTotoal'])->name('property.indexSubastaTotoal');
        Route::get('/{id}', [PropertyControllers::class, 'show'])->name('property.show');
        Route::put('/{id}/estado', [PropertyControllers::class, 'update'])->name('property.update');
    });

    Route::get('/subastadas', [PropertyControllers::class, 'subastadas'])->name('property.subastadas');


    Route::prefix('online')->group(function () {
        Route::get('/inversiones/{property_id}', [InvestmentControllers::class, 'index']);
    });

    Route::post('/investments', [InvestmentControllers::class, 'store'])->name('bids.index');
    Route::get('/inversiones/usuario', [InvestmentControllers::class, 'indexUser']);

    Route::get('/currencies', [CurrencyControllers::class, 'index']);
    Route::get('/deadlines', [DeadlinesControllers::class, 'index']);
});
Route::post('/calculate', [InvestmentControllers::class, 'simulateByAmount']);
    Route::get('/property/{id}/show', [PropertyControllers::class, 'showCustumer']);

Route::prefix('investments')->group(function () {
    Route::post('/simulation/generate', [InvestmentController::class, 'generate']);
});

Route::get('/Tipo-Cambio-Sbs', [TipoCambioSbs::class, 'TipoCambioSbs']);
Route::post('/calculadora', [CalculadoraController::class, 'calcular']);
Route::post('simulation/generate', [CreditSimulationController::class, 'generateSimulation']);