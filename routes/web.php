<?php

use App\Http\Controllers\Api\ConsultasDni;
use App\Http\Controllers\Api\ConsultasRucController;
use App\Http\Controllers\Api\FixedTermScheduleController;
use App\Http\Controllers\Api\MovementController;
use App\Http\Controllers\Api\PropertyReservationController;
use App\Http\Controllers\Api\RolesController;
use App\Http\Controllers\Api\UsuariosController;
use App\Http\Controllers\Panel\AmountRangeController;
use App\Http\Controllers\Panel\BankAccountsController;
use App\Http\Controllers\Panel\BidControllers;
use App\Http\Controllers\Panel\CalculadoraController;
use App\Http\Controllers\Panel\CargoController;
use App\Http\Controllers\Panel\CompanyController;
use App\Http\Controllers\Panel\CorporateEntityController;
use App\Http\Controllers\Panel\CurrencyControllers;
use App\Http\Controllers\Panel\CustomerController;
use App\Http\Controllers\Panel\DeadlinesControllers;
use App\Http\Controllers\Panel\DepositController;
use App\Http\Controllers\Panel\ExchangeController;
use App\Http\Controllers\Panel\FixedTermRateController;
use App\Http\Controllers\Panel\InvestmentControllers;
use App\Http\Controllers\Panel\InvestorController;
use App\Http\Controllers\Panel\InvoiceController;
use App\Http\Controllers\Panel\PagosController;
use App\Http\Controllers\Panel\PaymentFrequencyController;
use App\Http\Controllers\Panel\PaymentScheduleController;
use App\Http\Controllers\Panel\PaymentsController;
use App\Http\Controllers\Panel\PreviuController;
use App\Http\Controllers\Panel\PropertyControllers;
use App\Http\Controllers\Panel\PropertyLoanDetailController;
use App\Http\Controllers\Panel\RateTypeController;
use App\Http\Controllers\Panel\SectorController;
use App\Http\Controllers\Panel\SubSectorController;
use App\Http\Controllers\Panel\TermPlanController;
use App\Http\Controllers\Panel\WithdrawController;
use App\Http\Controllers\Web\Factoring\BankAccountsWeb;
use App\Http\Controllers\Web\Factoring\CompanyWeb;
use App\Http\Controllers\Web\Factoring\DepositsWeb;
use App\Http\Controllers\Web\Factoring\ExchangeWeb;
use App\Http\Controllers\Web\Factoring\ExchangeWebControler;
use App\Http\Controllers\Web\Factoring\InvestmentWeb;
use App\Http\Controllers\Web\Factoring\InvestorWeb;
use App\Http\Controllers\Web\Factoring\InvoiceWeb;
use App\Http\Controllers\Web\Factoring\PaymentsWeb;
use App\Http\Controllers\Web\Factoring\SectorWeb;
use App\Http\Controllers\Web\Factoring\SubSectorWeb;
use App\Http\Controllers\Web\Factoring\WithdrawWeb;
use App\Http\Controllers\Web\SubastaHipotecas\ClienteWebController;
use App\Http\Controllers\Web\SubastaHipotecas\CuentasBancariasWebControler;
use App\Http\Controllers\Web\SubastaHipotecas\DepositosWebControler;
use App\Http\Controllers\Web\SubastaHipotecas\HistoricoWebController;
use App\Http\Controllers\Web\SubastaHipotecas\InversionistasWebController;
use App\Http\Controllers\Web\SubastaHipotecas\PagosWebControler;
use App\Http\Controllers\Web\SubastaHipotecas\PropiedadesWebControler;
use App\Http\Controllers\Web\SubastaHipotecas\ReglasWebController;
use App\Http\Controllers\Web\SubastaHipotecas\RetirosWebControler;
use App\Http\Controllers\Web\SubastasOnlineWebController;
use App\Http\Controllers\Web\SubastaHipotecas\TipoCambioWebControler;
use App\Http\Controllers\Web\TasasFijas\DepositosWebController;
use App\Http\Controllers\Web\TasasFijas\EmpresasWebController;
use App\Http\Controllers\Web\TasasFijas\PagosWebController;
use App\Http\Controllers\Web\TasasFijas\PaymentFrequenciesWebController;
use App\Http\Controllers\Web\TasasFijas\RateTypeWebController;
use App\Http\Controllers\Web\TasasFijas\TermPlanWebController;
use App\Http\Controllers\Web\UsuarioWebController;
use App\Http\Controllers\BlogController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');
Route::get('/investors/{id}', [InvestorController::class, 'show']);

Route::middleware(['auth', 'verified'])->group(function () {
    #PARA QUE CUANDO SE CREA UN USUARIO O MODIFICA SU PASSWORD LO REDIRECCIONE PARA QUE PUEDA ACTUALIZAR
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    #VISTAS DEL FRONTEND
    Route::get('/usuario', [UsuarioWebController::class,'index'])->name('index.view');
    Route::get('/roles', [UsuarioWebController::class, 'roles'])->name('roles.view');
    Route::get('/online', [SubastasOnlineWebController::class, 'views'])->name('online.view');
    Route::get('/Ambiente-Pruebas', [SubastasOnlineWebController::class, 'viewsTC']);
    Route::get('/moneda', [CurrencyControllers::class, 'index']);
    Route::get('/Frecuencia/Pagos', [PaymentFrequenciesWebController::class, 'views']);

    #RUTAS DE API
    Route::prefix('api')->group(function () {
        Route::get('/consultar-ruc/{ruc?}', [ConsultasRucController::class, 'consultar'])->name('consultar.ruc');
    });

    #Cargos
    Route::prefix('cargos')->group(function(){
        Route::get('/', [CargoController::class, 'index'])->name('cargos.index');
    });

    #RUTAS DE WEB EN LA PARTE DE FACTORING
    
    Route::prefix('factoring')->group(function () {
        Route::get('/empresas', [CompanyWeb::class, 'views'])->name('empresas.views');
        Route::get('/sectores', [SectorWeb::class, 'views'])->name('sectores.views');
        Route::get('/{id}/subsectors', [SubSectorWeb::class, 'views'])->name('subsectors.views');
        Route::get('/inversionistas', [InvestorWeb::class, 'views'])->name('inversionistas.views');
        Route::get('/facturas', [InvoiceWeb::class, 'views'])->name('facturas.views');
        Route::get('/cuentas-bancarias', [BankAccountsWeb::class, 'views'])->name('bancarias.views');
        Route::get('/depositos', [DepositsWeb::class, 'views'])->name('depositos.views');
        Route::get('/{invoice_id}/inversionistas', [InvestmentWeb::class, 'views'])->name('inversionistas.views');
        Route::get('/inversiones', [InvestmentWeb::class, 'viewsGeneral'])->name('inversiones.viewsGeneral');
        Route::get('/pagos', [PaymentsWeb::class, 'views'])->name('inversiones.views');
        Route::get('/tipo-cambio', [ExchangeWebControler::class, 'views'])->name('tipo-cambio.views');
        Route::get('/retiros', [WithdrawWeb::class, 'views'])->name('retiros.views');
        Route::get('/tipo-cambio', [ExchangeWeb::class, 'views'])->name('retiros.views');
    });

    #RUTAS DE WEB EN LA PARTE DE TASAS FIJAS
    Route::prefix('tasas-fijas')->group(function(){
        Route::get('/depositos', [DepositosWebController::class, 'views'])->name('depositos.views');
        Route::get('/empresas', [EmpresasWebController::class, 'views'])->name('empresas.views');
        Route::get('/pagos', [PagosWebController::class, 'views'])->name('pagos.views');
        Route::get('/planes', [TermPlanWebController::class, 'views'])->name('planes.views');
        Route::get('/tipos', [RateTypeWebController::class, 'views'])->name('tipos.views');
    });

    #RUTAS DE WEB EN LA PARTE DE SUBASTA HIPOTECARIA
    Route::prefix('subasta-hipotecas')->group(function(){
        Route::get('/reserva', [CuentasBancariasWebControler::class, 'views'])->name('reserva.views');
        Route::get('/depositos', [DepositosWebControler::class, 'views'])->name('depositos.views');
        Route::get('/historicos', [HistoricoWebController::class, 'views'])->name('historicos.views');
        Route::get('/pagos', [PagosWebControler::class, 'views'])->name('pagos.views');
        Route::get('/propiedades', [PropiedadesWebControler::class, 'views'])->name('propiedades.views');
        Route::get('/retiros', [RetirosWebControler::class, 'views'])->name('retiros.views');
        Route::get('/tipo-cambio', [TipoCambioWebControler::class, 'views'])->name('tipo-cambio.views');
        Route::get('/reglas', [ReglasWebController::class, 'views'])->name('reglas.views');
        Route::get('/inversionista/pagos', [InversionistasWebController::class, 'index']);
        Route::get('/inversionista', [InversionistasWebController::class, 'views'])->name('inversionista.views');
        Route::get('/cliente/pagos', [ClienteWebController::class, 'views']);
    });

    #TIPO DE CAMBIP => BACKEND
    Route::prefix('exchange')->group(function () {
        Route::get('/', [ExchangeController::class, 'index'])->name('exchange.index');
        Route::get('/list', [ExchangeController::class, 'indexList']);
        Route::get('/{id}', [ExchangeController::class, 'show'])->name('exchange.show');
        Route::post('/', [ExchangeController::class, 'store'])->name('exchange.store');
        Route::put('/{id}', [ExchangeController::class, 'update'])->name('exchange.update');
        Route::delete('/{id}', [ExchangeController::class, 'destroy'])->name('exchange.destroy');
        // Cambiar estado
        Route::put('/{id}/activacion', [ExchangeController::class, 'activacion'])->name('exchange.activacion');
        Route::post('/{id}/inactivo', [ExchangeController::class, 'inactivo'])->name('exchange.inactivo');
    });

    Route::prefix('withdraws')->group(function () {
        Route::get('/', [WithdrawController::class, 'index'])->name('withdraws.index');
        Route::get('/{id}', [WithdrawController::class, 'show'])->name('withdraws.show');
        Route::post('/', [WithdrawController::class, 'store'])->name('withdraws.store');
        Route::put('/{id}', [WithdrawController::class, 'update'])->name('withdraws.update');
        Route::delete('/{id}', [WithdrawController::class, 'destroy'])->name('withdraws.destroy');

        Route::put('/{id}/approve', [WithdrawController::class, 'approve'])->name('withdraws.approve');
        Route::put('/{id}/reject', [WithdrawController::class, 'reject'])->name('withdraws.reject');
    });

    Route::prefix('investment')->group(function() {
        Route::get('/all', [InvestmentControllers::class, 'indexAll']);
        Route::get('/{invoice_id}', [InvestmentControllers::class, 'show']);
    });

    Route::prefix('deposit')->group(function () {
        Route::get('/', [DepositController::class, 'index'])->name('deposits.index');
        Route::get('/{id}', [DepositController::class, 'show'])->name('deposits.show');

        Route::post('/{movementId}/validate', [DepositController::class, 'validateDeposit'])
            ->name('deposits.validate');

        Route::post('/{depositId}/{movementId}/reject', [DepositController::class, 'rejectDeposit'])
            ->name('deposits.reject');

        Route::post('/{depositId}/{movementId}/approve', [DepositController::class, 'approveDeposit'])
            ->name('deposits.approve');

        Route::post('/{depositId}/{movementId}/reject-confirm', [DepositController::class, 'rejectConfirmDeposit'])
            ->name('deposits.rejectConfirm');
    });

    Route::prefix('ban')->group(function () {
        Route::get('/', [BankAccountsController::class, 'index'])->name('bankaccounts.index');

        // Rutas más específicas SIEMPRE van antes
        Route::get('/{bankAccountId}/filtrar', [BankAccountsController::class, 'showBank'])->name('bankaccounts.filtrar');

        Route::get('/{id}', [BankAccountsController::class, 'show'])->name('bankaccounts.show');
        Route::post('/{id}/validate', [BankAccountsController::class, 'validateBankAccount'])->name('bankaccounts.validate');
        Route::post('/{id}/reject', [BankAccountsController::class, 'rejectBankAccount'])->name('bankaccounts.reject');
    });

    # INVERSIONISTA -> BACKEND
    Route::prefix('investor')->group(function () {
        Route::get('/', [InvestorController::class, 'index'])->name('investor.index');
        Route::get('/{id}', [InvestorController::class, 'showInvestor'])->name('investor.showInvestor');
        Route::put('/{id}/aprobar', [InvestorController::class, 'aprobar'])->name('investor.aprobar');
        Route::put('/{id}/rechazar', [InvestorController::class, 'rechazar'])->name('investor.rechazar');
    });

    # COMPANIA -> BACKEND
    Route::prefix('companies')->group(function () {
        Route::get('/',        [CompanyController::class, 'index'])->name('companies.index');
        Route::post('/',       [CompanyController::class, 'store'])->name('companies.store');
        Route::get('/search',  [CompanyController::class, 'searchCompany'])->name('companies.searchCompany');
        Route::get('/export-excel', [CompanyController::class, 'exportExcel'])->name('companies.exportExcel');
        Route::get('{id}',     [CompanyController::class, 'show'])->name('companies.show');
        Route::put('{id}',     [CompanyController::class, 'update'])->name('companies.update');
        Route::delete('{id}',  [CompanyController::class, 'delete'])->name('companies.delete');
    });

    #SUB SECTOR -> BACKEND
    Route::prefix('subsectors')->group(function () {
        Route::get('/sector/{sector_id}', [SubSectorController::class, 'index'])->name('subsectors.index');
        Route::post('/', [SubSectorController::class, 'store'])->name('subsectors.store');
        Route::get('/{id}', [SubSectorController::class, 'show'])->name('subsectors.show');
        Route::put('/{id}', [SubSectorController::class, 'update'])->name('subsectors.update');
        Route::delete('/{id}', [SubSectorController::class, 'delete'])->name('subsectors.delete');

        #Filtrar por sector
        Route::get('/search/{sector_id}', [SubSectorController::class, 'searchSubSector'])
            ->name('subsectors.search');
    });


    #SECTOR -> BACKEND
    Route::prefix('sectors')->group(function () {
        Route::get('/', [SectorController::class, 'index'])->name('sectors.index');
        Route::post('/', [SectorController::class, 'store'])->name('sectors.store');
        #Buscador
        Route::get('/search', [SectorController::class, 'searchSector'])->name('sectors.search');
        Route::get('/{id}', [SectorController::class, 'show'])->name('sectors.show');
        Route::put('/{id}', [SectorController::class, 'update'])->name('sectors.update');
        Route::delete('/{id}', [SectorController::class, 'delete'])->name('sectors.delete');
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

    # INVOICES => BACKEND
    Route::prefix('invoices')->group(function () {
        Route::get('/filtrado', [InvoiceController::class, 'indexfilter']);
        Route::get('/', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::post('/', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::put('/{id}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::patch('/{id}/standby', [InvoiceController::class, 'standby']);
        Route::patch('/{id}/activacion', [InvoiceController::class, 'activacion']);
        Route::get('/{id}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::delete('/{id}', [InvoiceController::class, 'delete'])->name('invoices.delete');
        # Exportación a Excel
        Route::get('/export/excel', [InvoiceController::class, 'exportExcel'])->name('invoices.export');
    });

    #PROPERTY => BACKEND (SOLO ADMINISTRADOR MAS NO CLIENTE)
    Route::prefix('property')->group(function () {
        Route::get('/', [PropertyControllers::class, 'index'])->name('property.index');
        Route::get('/reglas', [PropertyControllers::class, 'listReglas'])->name('property.listReglas');
        Route::post('/', [PropertyControllers::class, 'store'])->name('property.store');
        Route::get('/{id}', [PropertyControllers::class, 'show'])->name('propertys.shows');
        Route::put('/{id}/estado', [PropertyControllers::class, 'update'])->name('property.update');
        Route::get('/{id}/show', [PropertyControllers::class, 'showProperty'])->name('show.show');
        Route::put('/{id}/actualizar', [PropertyControllers::class, 'updateProperty'])->name('actualizar.update');
        Route::delete('/{id}', [PropertyControllers::class, 'delete'])->name('property.delete');
        Route::get('/reglas/{id}/show', [PropertyControllers::class, 'showReglas']);
        Route::post('/enviar-emails', [PropertyControllers::class, 'enviar']);
    });
    
    Route::get('/propiedad/{id}/cronograma', [PaymentScheduleController::class, 'getCronogramaPorPropiedad']);
    Route::get('/cronograma/{property_investor_id}', [PaymentScheduleController::class, 'getCronograma']);
    #Seccion de apis x mientas
    Route::prefix('api')->group(function () {
        #Route::post('/bids', [BidControllers::class, 'index'])->name(name: 'bids.index');
    });

    #ESTA ES UNA CALCULADORA SOLO SIMULACION NO SUMA NI RESTA
    Route::prefix('Calculadora')->group(function () {
        Route::get('/', [CalculadoraController::class,'calcular']);
    });

    Route::prefix('coperativa')->group(function(){
        Route::get('/', [CorporateEntityController::class, 'index']);
        Route::post('/', [CorporateEntityController::class, 'store']);
        Route::get('{id}', [CorporateEntityController::class, 'show']);
        Route::put('{id}', [CorporateEntityController::class, 'update']);
        Route::delete('{id}', [CorporateEntityController::class, 'delete']);
        Route::get('{id}/pdf', [CorporateEntityController::class, 'showPdf']);
    });

    Route::prefix('term-plans')->group(function(){
        Route::post('/', [TermPlanController::class, 'store']);
        Route::get('/', [TermPlanController::class, 'list']);
        Route::put('/{termPlan}', [TermPlanController::class, 'update']);

    });

    Route::get('/deadlines', [DeadlinesControllers::class, 'index']);

    Route::apiResource('payment-frequencies', PaymentFrequencyController::class);
    
    Route::prefix('rate-types')->name('rate-types.')->group(function () {
        Route::get('/', [RateTypeController::class, 'list']);
        Route::post('/', [RateTypeController::class, 'store']);
        Route::get('/{rate_type}', [RateTypeController::class, 'show']);
        Route::put('/{rate_type}', [RateTypeController::class, 'update']);
        Route::delete('/{rate_type}', [RateTypeController::class, 'destroy']);
    });

    Route::prefix('amount-ranges')->controller(AmountRangeController::class)->group(function () {
        Route::get('{empresaId}', 'show');
        Route::get('{empresaId}/pendientes', 'showPendientes');
        Route::post('', 'store');
        Route::delete('{id}', 'delete');
    });
    
    Route::get('/fixed-term-matrix/{empresaId}', [FixedTermRateController::class, 'matrix']);

    #BUSCADPRES
    Route::get('/propiedades/pendientes', [PropertyControllers::class, 'listProperties']);
    Route::get('/propiedades/activas', [PropertyControllers::class, 'listPropertiesActivas']);
    Route::get('/clientes/activos', [CustomerController::class, 'listCustomersActivos']);

    Route::prefix('simulacion')->group(function () {
        Route::post('/preview-americano', [PreviuController::class, 'previewManual']);
        Route::post('/preview-frances', [PreviuController::class, 'previewFrances']);
    });

    Route::prefix('property-loan-details')->group(function () {
        Route::get('/', [PropertyLoanDetailController::class, 'index']);
        Route::post('/', [PropertyLoanDetailController::class, 'store']);
        Route::get('/{id}', [PropertyLoanDetailController::class, 'show']);
        Route::put('/{id}', [PropertyLoanDetailController::class, 'update']);
        Route::delete('/{id}', [PropertyLoanDetailController::class, 'destroy']);
    });
    
    Route::post('/properties/{id}/activacion', [PropertyLoanDetailController::class, 'activacion']);
    
    Route::prefix('fixed-term-rates')->controller(FixedTermRateController::class)->group(function () {
        Route::post('/bulk', 'storeBulk');
        Route::get('/entidad/{id}', 'showByEntidad');
        Route::post('/plazos-temas', [FixedTermRateController::class, 'storePlazoTema']);
        Route::put('/{id}', [FixedTermRateController::class, 'update']);
    });
    Route::post('/fixed-term-rates', [FixedTermRateController::class, 'store']);

    Route::prefix('movements')->group(function () {
        Route::get('/tasas-fijas', [MovementController::class, 'listTasasFijas']);
        Route::get('/hipotecas', [MovementController::class, 'listHipotecas']);
        Route::get('/pago/cliente', [MovementController::class, 'listPagosCliente']);

        Route::post('/{id}/aceptar-tasas-fijas', [MovementController::class, 'aceptarTasasFijas']);
        Route::post('/{id}/aceptar-hipotecas', [MovementController::class, 'aceptarHipotecas']);
        Route::post('/{id}/aceptar-pago/cliente', [MovementController::class, 'aceptarPagosCliente']);

        Route::post('/{id}/rechazar-tasas-fijas', [MovementController::class, 'rechazarTasasFijas']);
        Route::post('/{id}/rechazar-hipotecas', [MovementController::class, 'rechazarHipotecas']);
        Route::post('/{id}/rechazar/pago/cliente', [MovementController::class, 'rechazarPagosCliente']);

        Route::post('{id}/validate', [MovementController::class, 'validateMovement']);
    });

    Route::prefix('pagos')->group(function () {
        Route::get('/pendientes', [PagosController::class, 'pendientes']);
        Route::get('/cliente/pendiente', [PagosController::class, 'PagosHipotecas']);
    });

    Route::prefix('reservas-propiedades')->group(function () {
        Route::get('/', [PropertyReservationController::class, 'inversionistasConPendientes']);
        Route::get('/{id}', [PropertyReservationController::class, 'show']);
        Route::put('/{id}/cancelar', [PropertyReservationController::class, 'update']);
    });

    Route::get('/fixed-term-schedules/{id}/cronograma', [FixedTermScheduleController::class, 'showCronograma']);
    Route::get('/consultas/propiedad/{property_investor_id}/cronograma', [PaymentScheduleController::class, 'getCronogramaPorUsuario']);

    Route::prefix('pagos-tasas')->group(function () {
        Route::post('/', [PagosController::class, 'store']);
        Route::get('/', [PagosController::class, 'lis']);
    });

    Route::prefix('customer')->group(function () {
        Route::post('/', [InvestorController::class, 'store']);
    });

    Route::prefix('pagos-inversinotas')->group(function () {
        Route::post('/', [PagosController::class, 'storePayment']);
    });

    Route::get('/deposits/historial', [PagosController::class, 'listHistorial']);

    Route::get('bids', [BidControllers::class, 'index']);
    Route::get('bids/{id}', [BidControllers::class, 'show']);

    Route::get('/dni/{dni?}', [ConsultasDni::class, 'consultar']);

    Route::get('/blog/registro', [BlogController::class, 'create']);
    Route::get('/blog/seguimiento', [BlogController::class, 'seguimiento']);
    Route::get('/blog/categorias', [BlogController::class, 'categorias']);
    Route::get('/blog/posts', [BlogController::class, 'index']);

    Route::post('/payments/extraer', [PaymentsController::class, 'comparacion'])->name('payments.comparacion');
    Route::post('/payments/{invoiceId}', [PaymentsController::class, 'store'])->name('payments.store');

});


Route::get('/currencies', [CurrencyControllers::class, 'index']);
Route::get('/s3/{path}', function ($path) {
    if (!Storage::disk('s3')->exists($path)) {
        abort(404, 'Archivo no encontrado');
    }

    $file = Storage::disk('s3')->get($path);
    $extension = pathinfo($path, PATHINFO_EXTENSION);
    $mimeType = match ($extension) {
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
        default => 'application/octet-stream'
    };

    return response($file)
        ->header('Content-Type', $mimeType)
        ->header('Cache-Control', 'public, max-age=86400') // Cache por 24 horas
        ->header('ETag', md5($file))
        ->header('Last-Modified', Storage::disk('s3')->lastModified($path))
        ->header('Content-Length', Storage::disk('s3')->size($path));
})->where('path', '.*');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
