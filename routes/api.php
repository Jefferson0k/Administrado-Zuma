<?php

use App\Http\Controllers\Api\BalanceController;
use App\Http\Controllers\Api\BankAccountController;
use App\Http\Controllers\Api\BankController;
use App\Http\Controllers\Api\ConsultasDni;
use App\Http\Controllers\Api\ContactRequestController;
use App\Http\Controllers\Api\CreditSimulationController;
use App\Http\Controllers\Api\ExchangeControllerFonted;
use App\Http\Controllers\Api\FixedTermInvestmentControllers;
use App\Http\Controllers\Api\FixedTermScheduleController;
use App\Http\Controllers\Api\InvestmentController;
use App\Http\Controllers\Api\InvestorAvatarController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\MovementController;
use App\Http\Controllers\Api\NotificacionController;
use App\Http\Controllers\Api\PropertyReservationController;
use App\Http\Controllers\Api\TipoCambioSbs;
use App\Http\Controllers\Panel\CalculadoraController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\CustomerSessionController;
use App\Http\Controllers\Auth\RegisteredCustomerController;
use App\Http\Controllers\OCRController;
use App\Http\Controllers\OCRDniController;
use App\Http\Controllers\Panel\CompanyController;
use App\Http\Controllers\Panel\CurrencyControllers;
use App\Http\Controllers\Panel\DeadlinesControllers;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Panel\PropertyControllers;
use App\Http\Controllers\Panel\InvestmentControllers;
use App\Http\Controllers\Panel\InvestorController;
use App\Http\Controllers\Panel\PaymentScheduleController;
use App\Http\Controllers\Panel\PropertyInvestorController;
use App\Http\Controllers\Panel\VisitaProductoController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\Panel\DetalleInversionistaHipotecaController;
use App\Http\Controllers\Panel\SolicitudController;
use App\Http\Controllers\Panel\TwilioWebhookController;
use App\Http\Controllers\TipoDocumentoController;
use App\Http\Controllers\Web\SubastaHipotecas\TipoInmuebleController;

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS (sin login)
|--------------------------------------------------------------------------
*/

Route::post('register', [InvestorController::class, 'register']);
Route::post('register/cliente', [InvestorController::class, 'registerCustomer']);

/*
|--------------------------------------------------------------------------
| RUTA PARA EL SERVICIO DE SMS X WHTS
|--------------------------------------------------------------------------
*/

Route::post('/twilio/whatsapp/incoming', [TwilioWebhookController::class, 'handleIncomingMessage'])
    ->name('twilio.whatsapp.incoming');

Route::post('/twilio/whatsapp/status', [TwilioWebhookController::class, 'handleMessageStatus'])
    ->name('twilio.whatsapp.status');

Route::post('login', [InvestorController::class, 'login']);
Route::post('/customers/register', [RegisteredCustomerController::class, 'store']);
Route::put('/email/verify/{id}/{hash}', [ProfileController::class, 'emailVerification']);
Route::get('/consultar-dni/{dni?}', [ConsultasDni::class, 'consultar'])->name('consultar.dni');
Route::post('/email/verify/resend/{id}', [InvestorController::class, 'resendEmailVerification']);
Route::post('/contact-request', [ContactRequestController::class, 'storeContactUs']);
Route::post('/contact-request/internal', [ContactRequestController::class, 'storeInternal']);
Route::get('/contact/system-check', [ContactRequestController::class, 'systemCheck']);
Route::get('/contact/test-email', [ContactRequestController::class, 'testEmail']);
Route::get('/investors/{id}', [InvestorController::class, 'show']);
Route::put('/investors/{id}', [InvestorController::class, 'update']);
Route::get('/visitas-producto', [VisitaProductoController::class, 'visitasPorProducto']);
Route::post('/producto/{id}/click', [VisitaProductoController::class, 'registrar'])->name('producto.click');
Route::post('register', [InvestorController::class, 'register']);
Route::get('/investors/{id}', [InvestorController::class, 'showcliente']);
Route::put('/investors/{id}', [InvestorController::class, 'updatecliente']);
Route::post('reset-password', [InvestorController::class, 'resetPassword']);
Route::post('logout', [InvestorController::class, 'logout']);

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (requieren login con Sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/customers/logout', [CustomerSessionController::class, 'logout']);


    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'profile']);
        Route::get('/', [InvestorController::class, 'profile']);
        Route::get('/avatar', [InvestorAvatarController::class, 'getAvatar']);
        Route::post('/update-avatar', [InvestorController::class, 'updateAvatar']);
        Route::post('/update/confirm-account', [InvestorController::class, 'updateConfirmAccount']);
        Route::put('/update', [InvestorController::class, 'update']);
        Route::put('/update-password', [InvestorController::class, 'updatePassword']);

        Route::get('/last-invoice-invested', [InvestorController::class, 'lastInvoiceInvested']);
        // 👇 NUEVAS rutas de subida condicional
        Route::post('/{id}/document-front', [InvestorController::class, 'uploadDocumentFront']);
        Route::post('/{id}/document-back',  [InvestorController::class, 'uploadDocumentBack']);
        Route::post('/{id}/investor-photo', [InvestorController::class, 'uploadInvestorPhoto']);
    });

    Route::prefix('property')->group(function () {
        Route::get('/', [PropertyControllers::class, 'index'])->name('propertys.index');
        Route::get('/active/sow', [PropertyControllers::class, 'indexSubastaTotal'])->name('active.indexSubastaTotal');
        Route::get('/{id}', [PropertyControllers::class, 'show'])->name('property.show');
        Route::put('/{id}/estado', [PropertyControllers::class, 'update'])->name('estado.update');
    });

    Route::get('/subastadas', [PropertyControllers::class, 'subastadas'])->name('property.subastadas');
    Route::post('/xd/property', [InvestmentControllers::class, 'store'])->name('bids.index');
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

    Route::get('/propiedad/{property_investor_id}/cronograma', [PaymentScheduleController::class, 'getCronogramaPorUsuario']);
    Route::get('/propiedad/{property_investor_id}/cronograma/subasta', [PaymentScheduleController::class, 'Cronograma']);
    Route::post('/calculate', [InvestmentControllers::class, 'simulateByAmount']);

    Route::prefix('investments')->group(function () {
        #Factoring
        Route::get('/', [InvestmentController::class, 'index']);
        Route::post('/', [InvestmentController::class, 'invest']);
        Route::get('/{id}', [InvestmentController::class, 'showDetails']);

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

    Route::prefix('tasas-fijas')->group(function () {
        Route::get('/last', [FixedTermInvestmentControllers::class, 'last']);
        Route::get('/top', [FixedTermInvestmentControllers::class, 'top']);
        Route::get('/fixed-term-investments/pendientes', [FixedTermInvestmentControllers::class, 'pendingInvestments']);
    });


    Route::get('/fixed-term-schedules/{id}/cronograma', [FixedTermScheduleController::class, 'showCronograma']);
    Route::get('/config/{id}/schedules', [PropertyControllers::class, 'showConfig']);

    Route::prefix('reservas')->group(function () {
        Route::post('/', [PropertyReservationController::class, 'store']);
        Route::get('/', [PropertyReservationController::class, 'list']);
    });

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificacionController::class, 'list'])->name('notifications.list');
        Route::get('/missing-data', [NotificacionController::class, 'getMissingData'])->name('notifications.missing-data');
        Route::post('/mark-completed', [NotificacionController::class, 'markAsCompleted'])->name('notifications.mark-completed');
    });

    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::get('/paid', [InvoiceController::class, 'paid']);
        Route::get('/getSectors', [InvoiceController::class, 'getSectors']);
        Route::get('/{code}', [InvoiceController::class, 'show']);
    });

    Route::prefix(('companies'))->group(function () {
        Route::get('/{id}', [CompanyController::class, 'showcompany']);
        Route::get('/{id}/historical', [CompanyController::class, 'historicalData']);
    });

    Route::prefix('reports')->group(function () {
        Route::get('/balances', [BalanceController::class, 'index']);

        Route::prefix('investments')->group(function () {
            Route::get('/', [InvestmentController::class, 'reportTimeline']);
            Route::get('/group-by-company', [InvestmentController::class, 'reportGroupByCompany']);
            Route::get('/group-by-sector', [InvestmentController::class, 'reportGroupByCompanySector']);
        });

        Route::prefix('cumulative-returns')->group(function () {
            Route::get('/', [InvestmentController::class, 'reportCumulativeReturns']);
            Route::get('/group-by-company', [InvestmentController::class, 'reportCumulativeReturnsByCompany']);
            Route::get('/group-by-sector', [InvestmentController::class, 'reportCumulativeReturnsBySector']);
        });
    });

    Route::prefix('bank-accounts')->group(function () {
        Route::get('/', [BankAccountController::class, 'index']);
        Route::post('/', [BankAccountController::class, 'store']);
        Route::put('/{id}', [BankAccountController::class, 'update']);
        Route::delete('/{id}', [BankAccountController::class, 'destroy']);
    });

    Route::prefix('movements')->group(function () {
        Route::get('/', [MovementController::class, 'index']);
        Route::post('/deposits/create', [MovementController::class, 'createDeposit']);
        Route::post('/withdraw/create', [MovementController::class, 'createWithdraw']);
        Route::post('/deposits/tasas-fijas', [MovementController::class, 'createFixedRateDeposit']);
        Route::post('/deposits/hipotecas', [MovementController::class, 'createMortgageDeposit']);
        Route::post('/deposits/hipotecas', [MovementController::class, 'createMortgageDeposit']);
        Route::post('/deposits/zuma', [MovementController::class, 'createZumaDeposit']);
    });

    Route::prefix('banks')->group(function () {
        Route::get('/', [BankController::class, 'index']);
    });

    Route::prefix('exchange')->group(function () {
        Route::get('/', [ExchangeControllerFonted::class, 'index']);       // solo activo
        Route::get('/list', [ExchangeControllerFonted::class, 'indexList']); // historial completo
        Route::post('/pen-to-usd', [ExchangeControllerFonted::class, 'penToUsd']);
        Route::post('/usd-to-pen', [ExchangeControllerFonted::class, 'usdToPen']);
    });

    Route::post('/investors/{investor}/resend-whatsapp-verification', function (App\Models\Investor $investor) {
        $service = app(App\Services\WhatsAppVerificationService::class);
        $result = $service->resendVerificationMessage($investor);

        if ($result['success']) {
            return back()->with('success', 'Mensaje de verificación reenviado correctamente');
        } else {
            return back()->with('error', 'Error al reenviar: ' . $result['error']);
        }
    })->name('investors.resend-whatsapp-verification');

    Route::prefix('solicitud')->group(function () {
        Route::get('/{id}', [SolicitudController::class, 'show']);
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




Route::post('/blog/savecomentario', [BlogController::class, 'saveComentario']);
Route::get('/blog/publicaciones', [BlogController::class, 'publicaciones']);
Route::post('/blog/guardar-rating', [BlogController::class, 'guardar_rating']);


//Route::post('/blog/actualizar-categoria/{id}', [BlogController::class, 'actualizar_categoria']);

Route::get('/blog/listar-categoria', [BlogController::class, 'listar_categoria']);
Route::get('/blog/listar-categoria-filtrada/{id}', [BlogController::class, 'listar_categoria_filtrada']);
Route::get('/tipo-documentos', [TipoDocumentoController::class, 'index']);
Route::get('/blog/productos', [BlogController::class, 'productos']);
Route::get('/blog/showpost/{id}', [BlogController::class, 'showPost']);
Route::get('/blog/getcomentarios/{id}', [BlogController::class, 'getComentarios']);
Route::post('/detalle-inversionista', [DetalleInversionistaHipotecaController::class, 'store']);
Route::get('/tipo-inmueble', [TipoInmuebleController::class, 'index']);
