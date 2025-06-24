<?php

use App\Http\Controllers\Api\InvestmentController;
use App\Http\Controllers\Api\SimuladorController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\CustomerSessionController;
use App\Http\Controllers\Auth\RegisteredCustomerController;
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
        Route::get('/{id}', [PropertyControllers::class, 'show'])->name('property.show');
        Route::put('/{id}/estado', [PropertyControllers::class, 'update'])->name('property.update');
    });

    Route::get('/subastadas', [PropertyControllers::class, 'subastadas'])->name('property.subastadas');


    Route::prefix('online')->group(function () {
        Route::get('/inversiones/{property_id}', [InvestmentControllers::class, 'index']);
    });
    Route::post('/investments', [InvestmentControllers::class, 'store'])->name('bids.index');
    Route::get('/inversiones/usuario', [InvestmentControllers::class, 'indexUser']);
});

Route::post('/calculate', [InvestmentControllers::class, 'simulateByAmount']);

Route::prefix('investments')->group(function () {
    Route::post('/simulate-by-amount', [InvestmentController::class, 'simulateByAmount']);
    Route::post('/generate-schedule', [InvestmentController::class, 'generateSchedule']);
    Route::post('/complete-simulation', [InvestmentController::class, 'completeSimulation']);
    Route::post('/compare-rates', [InvestmentController::class, 'compareRates']);
    Route::post('/export-schedule', [InvestmentController::class, 'exportSchedule']);
    Route::get('/payment-frequencies', [InvestmentController::class, 'getPaymentFrequencies']);
});
