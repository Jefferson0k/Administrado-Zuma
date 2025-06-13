<?php

use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Panel\BidControllers;
use App\Http\Controllers\Panel\InvestmentControllers;
use App\Http\Controllers\Panel\PropertyControllers;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/invoices', [InvoiceController::class, 'index']);
});
