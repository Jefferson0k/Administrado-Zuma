<?php

use App\Http\Controllers\Api\ConsultasDni;
use App\Http\Controllers\Api\RolesController;
use App\Http\Controllers\Api\UsuariosController;
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
    Route::get('/consulta/{dni}', [ConsultasDni::class, 'consultar'])->name('consultar.view');
    Route::get('/roles', [UsuarioWebController::class, 'roles'])->name('roles.view');
    
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
    #RUTAS DE WEB EN LA PARTE DE PRESTAMOS
    Route::prefix('prestamos')->group(function(){
        
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
}); 

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';