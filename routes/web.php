<?php

use App\Http\Controllers\Api\ConsultasDni;
use App\Http\Controllers\Api\ConsultasRucController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\RolesController;
use App\Http\Controllers\Api\UsuariosController;
use App\Http\Controllers\Panel\BidControllers;
use App\Http\Controllers\Panel\CalculadoraController;
use App\Http\Controllers\Panel\CurrencyControllers;
use App\Http\Controllers\Panel\DeadlinesControllers;
use App\Http\Controllers\Panel\InvestmentControllers;
use App\Http\Controllers\Panel\PropertyControllers;
use App\Http\Controllers\Web\SubastaHipotecas\CuentasBancariasWebControler;
use App\Http\Controllers\Web\SubastaHipotecas\DepositosWebControler;
use App\Http\Controllers\Web\SubastaHipotecas\HistoricoWebController;
use App\Http\Controllers\Web\SubastaHipotecas\PagosWebControler;
use App\Http\Controllers\Web\SubastaHipotecas\PropiedadesWebControler;
use App\Http\Controllers\Web\SubastaHipotecas\RetirosWebControler;
use App\Http\Controllers\Web\SubastasOnlineWebController;
use App\Http\Controllers\Web\SubastaHipotecas\TipoCambioWebControler;
use App\Http\Controllers\Web\TasasFijas\CuentasBancariasWebController;
use App\Http\Controllers\Web\TasasFijas\DepositosWebController;
use App\Http\Controllers\Web\TasasFijas\EmpresasWebController;
use App\Http\Controllers\Web\TasasFijas\FacturasWebController;
use App\Http\Controllers\Web\TasasFijas\InversionesWebController;
use App\Http\Controllers\Web\TasasFijas\InversionistasWebController;
use App\Http\Controllers\Web\TasasFijas\PagosWebController;
use App\Http\Controllers\Web\TasasFijas\RetirosWebController;
use App\Http\Controllers\Web\TasasFijas\TipoCambioWebController;
use App\Http\Controllers\Web\UsuarioWebController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    #PARA QUE CUANDO SE CREA UN USUARIO O MODIFICA SU PASSWORD LO REDIRECCIONE PARA QUE PUEDA ACTUALIZAR
    Route::get('/dashboard', function () {
        $user = Auth::user();
        return Inertia::render('Dashboard', [
            'mustReset' => $user->restablecimiento == 0,
        ]);
    })->name('dashboard');

    #VISTAS DEL FRONTEND
    Route::get('/usuario', [UsuarioWebController::class,'index'])->name('index.view');
    Route::get('/roles', [UsuarioWebController::class, 'roles'])->name('roles.view');
    Route::get('/online', [SubastasOnlineWebController::class, 'views'])->name('online.view');
    Route::get('/Ambiente-Pruebas', [SubastasOnlineWebController::class, 'viewsTC']);
    Route::get('/moneda', [CurrencyControllers::class, 'index']);

    #RUTAS DE API
    Route::prefix('api')->group(function () {
        Route::get('/consultar-dni/{dni?}', [ConsultasDni::class, 'consultar'])->name('consultar.dni');
        Route::get('/consultar-ruc/{ruc?}', [ConsultasRucController::class, 'consultar'])->name('consultar.ruc');
    });

    #RUTAS DE WEB EN LA PARTE DE TASAS FIJAS
    Route::prefix('tasas-fijas')->group(function(){
        Route::get('/cuentas-bancarias', [CuentasBancariasWebController::class, 'views'])->name('cuentas-bancarias.views');
        Route::get('/depositos', [DepositosWebController::class, 'views'])->name('depositos.views');
        Route::get('/empresas', [EmpresasWebController::class, 'views'])->name('empresas.views');
        Route::get('/facturas', [FacturasWebController::class, 'views'])->name('facturas.views');
        Route::get('/inversiones', [InversionesWebController::class, 'views'])->name('inversiones.views');
        Route::get('/inversionistas', [InversionistasWebController::class, 'views'])->name('inversionistas.views');
        Route::get('/pagos', [PagosWebController::class, 'views'])->name('pagos.views');
        Route::get('/retiros', [RetirosWebController::class, 'views'])->name('retiros.views');
        Route::get('/tipo-cambio', [TipoCambioWebController::class, 'views'])->name('tipo-cambio.views');
    });

    #RUTAS DE WEB EN LA PARTE DE SUBASTA HIPOTECARIA
    Route::prefix('subasta-hipotecas')->group(function(){
        Route::get('/cuentas-bancarias', [CuentasBancariasWebControler::class, 'views'])->name('cuentas-bancarias.subasta.views');
        Route::get('/depositos', [DepositosWebControler::class, 'views'])->name('depositos.views');
        Route::get('/historicos', [HistoricoWebController::class, 'views'])->name('historicos.views');
        Route::get('/inversionistas', [InversionistasWebController::class, 'views'])->name('inversionistas.views');
        Route::get('/pagos', [PagosWebControler::class, 'views'])->name('pagos.views');
        Route::get('/propiedades', [PropiedadesWebControler::class, 'views'])->name('propiedades.views');
        Route::get('/retiros', [RetirosWebControler::class, 'views'])->name('retiros.views');
        Route::get('/tipo-cambio', [TipoCambioWebControler::class, 'views'])->name('tipo-cambio.views');
    });

    #USUARIOS -> BACKEND
    Route::prefix('usuarios')->group(function(){
        Route::get('/', [UsuariosController::class, 'index'])->name('usuarios.index');
        Route::post('/',[UsuariosController::class, 'store'])->name('usuarios.store');
        Route::get('/{user}',[UsuariosController::class, 'show'])->name('usuarios.show');
        Route::put('/{user}',[UsuariosController::class, 'update'])->name('usuarios.update');
        Route::delete('/{user}',[UsuariosController::class, 'destroy'])->name('usuarios.destroy');
    });

    #ROLES => BACKEND
    Route::prefix('rol')->group(function () {
        Route::get('/', [RolesController::class, 'index'])->name('roles.index');
        Route::get('/Permisos', [RolesController::class, 'indexPermisos'])->name('roles.indexPermisos');
        Route::post('/', [RolesController::class, 'store'])->name('roles.store');
        Route::get('/{id}', [RolesController::class, 'show'])->name('roles.show');
        Route::put('/{id}', [RolesController::class, 'update'])->name('roles.update');
        Route::delete('/{id}', [RolesController::class, 'destroy'])->name('roles.destroy');
    });

    #INVOICES => BACKEND
    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('invoices.index');
        // Add other invoice routes here as needed
    });

    #PROPERTY => BACKEND (SOLO ADMINISTRADOR MAS NO CLIENTE)
    Route::prefix('property')->group(function () {
        Route::get('/', [PropertyControllers::class, 'index'])->name('property.index');
        Route::post('/', [PropertyControllers::class, 'store'])->name('property.store');
        Route::get('/{id}', [PropertyControllers::class, 'show'])->name('property.show');
        Route::put('/{id}/estado', [PropertyControllers::class, 'update'])->name('property.update');
    });
    
    #Seccion de apis x mientas
    Route::prefix('api')->group(function () {
        #Route::post('/bids', [BidControllers::class, 'index'])->name(name: 'bids.index');
    });

    #ESTA ES UNA CALCULADORA SOLO SIMULACION NO SUMA NI RESTA
    Route::prefix('Calculadora')->group(function () {
        Route::get('/', [CalculadoraController::class,'calcular']);
    });
    Route::get('/deadlines', [DeadlinesControllers::class, 'index']);
}); 
    Route::get('/currencies', [CurrencyControllers::class, 'index']);

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
