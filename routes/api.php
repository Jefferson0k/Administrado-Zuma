<?php

use App\Http\Controllers\Auth\RegisteredCustomerController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\CustomerSessionController;
use App\Http\Controllers\Panel\PropertyControllers;
use App\Http\Controllers\Settings\ProfileController;

// INICIO DE SESIÓN
Route::post('/login', [CustomerSessionController::class, 'login']);

// REGISTRO DE CLIENTE
Route::post('/customers/register', [RegisteredCustomerController::class, 'store']);
Route::post('/email/verify/resend/{id}', [ProfileController::class, 'resendEmailVerification']);
Route::put('/email/verify/{id}/{hash}', [ProfileController::class, 'emailVerification']);

// RUTAS PROTEGIDAS CON SANCTUM
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'profile']);
    });

    Route::prefix('property')->group(function () {
        Route::get('/', [PropertyControllers::class, 'index'])->name('property.index');
        Route::get('/subastadas', [PropertyControllers::class, 'subastadas'])->name('property.subastadas');
    });
});

// CERRAR SESIÓN
Route::middleware('auth:customer')->post('/customers/logout', [CustomerSessionController::class, 'logout']);
