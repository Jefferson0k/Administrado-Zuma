<?php

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

    Route::prefix('loan-simulation')->group(function () {
        Route::post('/simulate', [LoanSimulationController::class, 'simulate']);
        Route::get('/options', [LoanSimulationController::class, 'getOptions']);
        Route::get('/amount-ranges/{corporate_entity_id}', [LoanSimulationController::class, 'getAmountRanges']);
        Route::get('/term-plans', [LoanSimulationController::class, 'getTermPlans']);
    });

    Route::prefix('online')->group(function () {
        Route::get('/inversiones/{property_id}', [InvestmentControllers::class, 'index']);
    });
    Route::post('/investments', [InvestmentControllers::class, 'store'])->name('bids.index');
    Route::get('/inversiones/usuario', [InvestmentControllers::class, 'indexUser']);
});