<?php

use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Panel\BidControllers;
use App\Http\Controllers\Panel\InvestmentControllers;
use App\Http\Controllers\Panel\LoanSimulationController;
use App\Http\Controllers\Panel\PropertyControllers;
use App\Http\Controllers\Panel\SimulacionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/invoices', [InvoiceController::class, 'index']);
});

Route::post('/bids', [BidControllers::class, 'index'])->name(name: 'bids.index');
Route::get('/inversiones/{property_id}', [InvestmentControllers::class, 'index']);
Route::get('/subastadas', [PropertyControllers::class, 'subastadas'])->name('property.subastadas');
Route::get('/historial/user', [InvestmentControllers::class, 'indexUser']);
Route::prefix('loan-simulation')->group(function () {
    Route::post('/simulate', [LoanSimulationController::class, 'simulate']);
    Route::get('/options', [LoanSimulationController::class, 'getOptions']);
    Route::get('/amount-ranges/{corporate_entity_id}', [LoanSimulationController::class, 'getAmountRanges']);
    Route::get('/term-plans', [LoanSimulationController::class, 'getTermPlans']);
});

