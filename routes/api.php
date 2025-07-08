<?php

use App\Http\Controllers\Api\CreditSimulationController;
use App\Http\Controllers\Api\FixedTermInvestmentControllers;
use App\Http\Controllers\Api\InvestmentController;
use App\Http\Controllers\Api\TipoCambioSbs;
use App\Http\Controllers\Panel\CalculadoraController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\CustomerSessionController;
use App\Http\Controllers\Auth\RegisteredCustomerController;
use App\Http\Controllers\OCRController;
use App\Http\Controllers\OCRDniController;
use App\Http\Controllers\Panel\CurrencyControllers;
use App\Http\Controllers\Panel\DeadlinesControllers;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Panel\PropertyControllers;
use App\Http\Controllers\Panel\InvestmentControllers;
use App\Http\Controllers\Panel\LoanSimulationController;
use App\Http\Controllers\Panel\PaymentScheduleController;
use App\Http\Controllers\Panel\PropertyInvestorController;

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
    Route::post('/investments', [InvestmentControllers::class, 'store'])->name('bids.index');
    Route::get('/inversiones/usuario', [InvestmentControllers::class, 'indexUser']);
    Route::get('/currencies', [CurrencyControllers::class, 'index']);
    Route::get('/deadlines', [DeadlinesControllers::class, 'index']);
    Route::get('/property/{id}/show', [PropertyControllers::class, 'showCustumer']);
    Route::post('/extract-text', [OCRController::class, 'extractText']);
    Route::post('/extract-dni', [OCRDniController::class, 'extractText']);
    Route::post('/invest-property', [PropertyInvestorController::class, 'store']);

    Route::prefix('investor')->group(function () {
        Route::get('/inversion', [PropertyInvestorController::class, 'inversion']);
        Route::get('/ranking', [PropertyInvestorController::class, 'ranquiSubastas']);
    });

    Route::get('/propiedad/{id}/cronograma', [PaymentScheduleController::class, 'getCronogramaPorPropiedad']);
    Route::post('/calculate', [InvestmentControllers::class, 'simulateByAmount']);
    
    Route::prefix('investments')->group(function () {
        Route::post('/simulate-by-amount', [InvestmentController::class, 'simulateByAmount']);
        Route::post('/generate-schedule', [InvestmentController::class, 'generateSchedule']);
        Route::post('/complete-simulation', [InvestmentController::class, 'completeSimulation']);
        Route::post('/compare-rates', [InvestmentController::class, 'compareRates']);
        Route::post('/export-schedule', [InvestmentController::class, 'exportSchedule']);
        Route::get('/payment-frequencies', [InvestmentController::class, 'getPaymentFrequencies']);
    });

    Route::prefix('panel')->group(function () {
        Route::post('/fixed-term-investments', [FixedTermInvestmentControllers::class, 'store']);
        Route::post('/fixed-term-investments/cronograma', [FixedTermInvestmentControllers::class, 'storeCronograma']);
        Route::get('/fixed-term-investments', [FixedTermInvestmentControllers::class, 'index']);
        Route::get('/fixed-term-investments/{id}', [FixedTermInvestmentControllers::class, 'show']);
    });
});

Route::prefix('investments')->group(function () {
    Route::post('/simulation/generate', [InvestmentController::class, 'generate']);
});
Route::post('simulation/generate', [CreditSimulationController::class, 'generateSimulation']);

Route::post('/calculadora', [CalculadoraController::class, 'calcular']);

Route::prefix('online')->group(function () {
     Route::get('/inversiones/{property_id}', [InvestmentControllers::class, 'index']);
});

Route::get('/Tipo-Cambio-Sbs', [TipoCambioSbs::class, 'TipoCambioSbs']);
