<?php

namespace App\Http\Controllers\Api;

use App\Enums\MovementStatus;
use App\Enums\MovementType;
use App\Helpers\MoneyConverter;
use App\Http\Controllers\Controller;
use App\Http\Requests\FixedRateDeposit\StoreFixedRateDepositRequest;
use App\Http\Requests\FixedRateDeposit\StoreMortgageDepositRequest;
use App\Http\Requests\FixedRateDeposit\StoreZumaDepositRequest;
use App\Http\Requests\Payments\StorePaymentsRequest;
use App\Http\Requests\Withdraw\StoreWithdrawRequest;
use App\Http\Resources\Tasas\Movement\MovementResource;
use App\Models\Bank;
use App\Models\Deposit;
use App\Models\Movement;
use App\Models\Investment;
use Illuminate\Support\Str;
use App\Models\PaymentSchedule;
use App\Models\Property;
use App\Models\PropertyInvestor;
use App\Models\PropertyReservation;
use App\Models\Withdraw;
use App\Models\BankAccountDestino;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Storage;
use Aws\S3\Exception\S3Exception;
use Throwable;

class MovementController extends Controller
{
    public function listTasasFijas(Request $request)
    {
        $movements = Movement::with('deposit')
            ->where('description', 'tasas_fijas')
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 10));

        return MovementResource::collection($movements)
            ->additional(['success' => true]);
    }
    public function listHipotecas(Request $request)
    {
        $movements = Movement::with('deposit')
            ->where('description', 'hipotecas')
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 10));

        return MovementResource::collection($movements)
            ->additional(['success' => true]);
    }
    public function listPagosCliente(Request $request)
    {
        $movements = Movement::with('deposit')
            ->where('description', 'zuma')
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 10));

        return MovementResource::collection($movements)
            ->additional(['success' => true]);
    }
    public function aceptarTasasFijas(string $id)
    {
        DB::transaction(function () use ($id) {
            $movement = Movement::with('deposit')->findOrFail($id);
            $movement->update([
                'status' => MovementStatus::VALID,
                'confirm_status' => MovementStatus::CONFIRMED,
            ]);
            $deposit = $movement->deposit;
            if ($deposit && $deposit->fixed_term_investment_id) {
                $deposit->fixedTermInvestment()->update([
                    'status' => 'activo',
                ]);
            }
        });
        return response()->json([
            'success' => true,
            'message' => 'Movimiento aceptado y estado de inversión activado.',
        ]);
    }
    public function rechazarTasasFijas(string $id)
    {
        DB::transaction(function () use ($id) {
            $movement = Movement::with('deposit')->findOrFail($id);
            $movement->update([
                'status' => MovementStatus::INVALID,
                'confirm_status' => MovementStatus::REJECTED,
            ]);
        });
        return response()->json([
            'success' => true,
            'message' => 'Movimiento rechazado correctamente.',
        ]);
    }
    public function aceptarHipotecas(string $id)
    {
        DB::transaction(function () use ($id) {
            $movement = Movement::with('deposit')->findOrFail($id);
            $movement->update([
                'status' => MovementStatus::VALID,
                'confirm_status' => MovementStatus::CONFIRMED,
            ]);

            $deposit = $movement->deposit;
            if ($deposit && $deposit->property_reservations_id) {
                $deposit->propertyreservacion()->update([
                    'status' => 'activo',
                ]);
            }

            $propertyReservation = PropertyReservation::find($id);

            if ($propertyReservation) {
                $propertyReservation->update([
                    'status' => 'pagado',
                ]);

                PropertyInvestor::where('config_id', $propertyReservation->config_id)->update([
                    'investor_id' => $propertyReservation->investor_id,
                ]);

                Property::where('id', $propertyReservation->property_id)->update([
                    'estado' => 'adquirido',
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Movimiento aceptado y estado de inversión activado. Reserva procesada si existe.',
        ]);
    }
    public function rechazarhipotecas(string $id)
    {
        DB::transaction(function () use ($id) {
            $movement = Movement::with('deposit')->findOrFail($id);
            $movement->update([
                'status' => MovementStatus::INVALID,
                'confirm_status' => MovementStatus::REJECTED,
            ]);
        });
        return response()->json([
            'success' => true,
            'message' => 'Movimiento rechazado correctamente.',
        ]);
    }
    public function aceptarPagosCliente(string $id)
    {
        DB::transaction(function () use ($id) {
            $movement = Movement::with('deposit')->findOrFail($id);
            $movement->update([
                'status' => MovementStatus::VALID,
                'confirm_status' => MovementStatus::CONFIRMED,
            ]);
            $deposit = $movement->deposit;
            if ($deposit && $deposit->payment_schedules_id) {
                $paymentSchedule = PaymentSchedule::findOrFail($deposit->payment_schedules_id);
                $paymentSchedule->update(['estado' => 'pagado']);
                $propertyInvestorId = $paymentSchedule->property_investor_id;
                $totalCuotas = PaymentSchedule::where('property_investor_id', $propertyInvestorId)->count();
                $cuotasPagadas = PaymentSchedule::where('property_investor_id', $propertyInvestorId)
                    ->where('estado', 'pagado')
                    ->count();
                if ($totalCuotas === $cuotasPagadas) {
                    PropertyInvestor::where('id', $propertyInvestorId)
                        ->update(['status' => 'finalizado']);
                }
            }
        });
        return response()->json([
            'success' => true,
            'message' => 'Movimiento aceptado y estado de inversión actualizado. Reserva procesada si existe.',
        ]);
    }
    public function rechazarPagosCliente(string $id)
    {
        DB::transaction(function () use ($id) {
            $movement = Movement::with('deposit')->findOrFail($id);
            $movement->update([
                'status' => MovementStatus::INVALID,
                'confirm_status' => MovementStatus::REJECTED,
            ]);
        });
        return response()->json([
            'success' => true,
            'message' => 'Movimiento rechazado correctamente.',
        ]);
    }
    public function index(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'currency' => [
                        'required',
                        'string',
                        function (string $attribute, mixed $value) {
                            if (strval($value) !== "PEN" && strval($value) !== "USD") {
                                throw new \Exception("The {$attribute} query {$value} is no valid.", 400);
                            }
                        }
                    ],
                ]
            );

            $validator->fails() ? $validator->throwException() : null;

            $token = PersonalAccessToken::findToken(request()->bearerToken());
            $investor = $token->tokenable;

            // PASO 1: Agregamos investment.invoice.company para obtener el nombre de la empresa
            $data = $investor->movements()
                ->with([
                    'investment',
                    'investment.invoice',
                    'investment.invoice.company', // Agregamos la relación con company
                    'deposit.bankAccount.bank',
                    'deposit.bankAccountDestino.bank',
                    

                ])
                ->when(
                    $request->filled('currency'),
                    function ($query) use ($request) {
                        $query->where('currency', $request->currency);
                    }
                )
                ->orderBy('created_at', 'desc')
                ->paginate(10)
                ->withPath('')
                ->setPageName($request->input('pageName') ?: 'page');

            // Transformamos los datos para agregar investor_id
            $data->getCollection()->transform(function ($movement) use ($investor) {
                $movement->investor_id = $investor->id;
                return $movement;
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => null,
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], $th->getCode() ?: 500);
        }
    }
    public function createDeposit(StorePaymentsRequest $request)
    {
        $validatedData = $request->validated();

        /** @var \App\Models\Investor $investor */
        $investor = Auth::user();

        // == LOG: Inicio + payload (sin exponer todo)
        Log::info('Deposit@createDeposit - START', [
            'user_id' => $investor?->id,
            'payload' => [
                'bank'          => $validatedData['bank'] ?? null,
                'bank_destino'  => $validatedData['bank_destino'] ?? null,
                'amount'        => $validatedData['amount'] ?? null,
                'nro_operation' => $validatedData['nro_operation'] ?? null,
                // No logeamos el archivo completo, sólo tipo/tamaño más abajo
            ],
        ]);

        try {
            // Valores del body
            $bankID          = $validatedData['bank'];
            $bankDestinoID   = $validatedData['bank_destino'];
            $amount          = $validatedData['amount'];
            $operationNumber = $validatedData['nro_operation'];
            $voucher         = $validatedData['voucher']; // UploadedFile

            // Buscar la cuenta bancaria del inversor
            $bank_account = $investor->bankAccounts()->where('id', $bankID)->first();
            $bank_account_destino = BankAccountDestino::where('id', $bankDestinoID)->first();

            if (!$bank_account) {
                Log::warning('Deposit@createDeposit - bank account not found for user', [
                    'user_id' => $investor->id,
                    'bank_id' => $bankID,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'La cuenta bancaria seleccionada no existe.',
                ], 404);
            }

            // Validar archivo
            if (!$voucher || !$voucher->isValid()) {
                Log::warning('Deposit@createDeposit - invalid file', [
                    'user_id'   => $investor->id,
                    'has_file'  => (bool) $voucher,
                    'is_valid'  => $voucher?->isValid(),
                    'error'     => $voucher?->getErrorMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'El archivo no es válido.',
                ], 400);
            }

            // LOG: Metadatos del archivo
            Log::info('Deposit@createDeposit - file meta', [
                'original_name' => $voucher->getClientOriginalName(),
                'extension'     => $voucher->getClientOriginalExtension(),
                'mime'          => $voucher->getMimeType(),
                'size_bytes'    => $voucher->getSize(),
                'tmp_path'      => $voucher->getRealPath(),
            ]);

            // Nombre de archivo
            $fileName = Str::slug($investor->document) . '-' . Str::slug($operationNumber) . '.' . $voucher->getClientOriginalExtension();

            // Disco S3
            $disk = Storage::disk('s3');

            // LOG: Snapshot de config S3 (sin secretos)
            Log::info('Deposit@createDeposit - S3 config snapshot', [
                'default_disk' => config('filesystems.default'),
                'driver'       => config('filesystems.disks.s3.driver'),
                'bucket'       => config('filesystems.disks.s3.bucket'),
                'region'       => config('filesystems.disks.s3.region'),
                'endpoint'     => config('filesystems.disks.s3.endpoint'),
                'url'          => config('filesystems.disks.s3.url'),
                'path_style'   => config('filesystems.disks.s3.use_path_style_endpoint'),
                'throw'        => config('filesystems.disks.s3.throw'),
            ]);

            // PRE-FLIGHT: headBucket si el adapter expone el cliente
            try {
                $adapter = method_exists($disk, 'getAdapter') ? $disk->getAdapter() : null;

                if ($adapter && method_exists($adapter, 'getClient')) {
                    $client = $adapter->getClient();
                    $bucket = config('filesystems.disks.s3.bucket');

                    $client->headBucket(['Bucket' => $bucket]);
                    Log::info('Deposit@createDeposit - headBucket OK', ['bucket' => $bucket]);
                } else {
                    Log::warning('Deposit@createDeposit - cannot access S3 client from adapter (no getClient method)');
                }
            } catch (S3Exception $e) {
                Log::error('Deposit@createDeposit - headBucket FAILED', [
                    'aws_error_code'  => method_exists($e, 'getAwsErrorCode') ? $e->getAwsErrorCode() : null,
                    'aws_error_type'  => method_exists($e, 'getAwsErrorType') ? $e->getAwsErrorType() : null,
                    'aws_request_id'  => method_exists($e, 'getAwsRequestId') ? $e->getAwsRequestId() : null,
                    'message'         => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'No se puede acceder al bucket S3/MinIO. Revisa endpoint/bucket/credenciales.',
                ], 500);
            } catch (\Throwable $e) {
                Log::error('Deposit@createDeposit - headBucket throwable', [
                    'class'   => get_class($e),
                    'message' => $e->getMessage(),
                ]);
                // seguimos, no bloquea la operación, pero queda log
            }

            // === SUBIDA ===
            $storedPath = null;

            try {
                // Opción A) putFileAs (simple)
                $storedPath = $disk->putFileAs('depositos', $voucher, $fileName);

                // Opción B) writeStream (útil para control fino de headers). Descomenta para probar:
                /*
            $stream = fopen($voucher->getRealPath(), 'r');
            $storedPath = 'depositos/'.$fileName;
            $disk->writeStream($storedPath, $stream, [
                'visibility'  => 'private',
                'ContentType' => $voucher->getMimeType(),
            ]);
            if (is_resource($stream)) fclose($stream);
            */

                if (!$storedPath) {
                    Log::error('Deposit@createDeposit - putFileAs returned false', [
                        'folder'  => 'depositos',
                        'file'    => $fileName,
                        'size'    => $voucher->getSize(),
                        'mime'    => $voucher->getMimeType(),
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Error al subir el archivo. Verifique la configuración de almacenamiento.',
                    ], 500);
                }

                Log::info('Deposit@createDeposit - upload OK', ['path' => $storedPath]);
            } catch (S3Exception $e) {
                Log::error('Deposit@createDeposit - upload S3Exception', [
                    'aws_error_code' => method_exists($e, 'getAwsErrorCode') ? $e->getAwsErrorCode() : null,
                    'aws_error_type' => method_exists($e, 'getAwsErrorType') ? $e->getAwsErrorType() : null,
                    'aws_request_id' => method_exists($e, 'getAwsRequestId') ? $e->getAwsRequestId() : null,
                    'message'        => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Error al subir el archivo (S3): ' . $e->getMessage(),
                ], 500);
            } catch (\Throwable $e) {
                Log::error('Deposit@createDeposit - upload throwable', [
                    'class'   => get_class($e),
                    'message' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Error al subir el archivo: ' . $e->getMessage(),
                ], 500);
            }

            // === URL temporal ===
            try {
                // Nota: en MinIO, temporaryUrl funciona si las credenciales/endpoint están correctas
                $urlTemporal = $disk->temporaryUrl(
                    'depositos/' . $fileName,
                    now()->addMinutes(60)
                );
                Log::info('Deposit@createDeposit - temporaryUrl OK');
            } catch (\Throwable $e) {
                Log::error('Deposit@createDeposit - temporaryUrl FAILED', [
                    'class'   => get_class($e),
                    'message' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Error al generar URL temporal: ' . $e->getMessage(),
                ], 500);
            }

            // === DB ===
            DB::beginTransaction();
            try {
                $deposit = new Deposit();
                $deposit->nro_operation   = $operationNumber;
                $deposit->currency        = $bank_account->currency;
                $deposit->amount          = $amount;
                $deposit->resource_path   = $storedPath; // path real
                $deposit->created_by      = $investor->id;
                $deposit->updated_by      = $investor->id;
                $deposit->investor_id     = $investor->id;
                $deposit->bank_account_id = $bank_account->id;
                $deposit->bank_account_destino_id = $bank_account_destino->id;

                $movement = new Movement();
                $movement->currency    = $bank_account->currency;
                $movement->amount      = $amount;
                $movement->type        = 'deposit';
                $movement->status      = MovementStatus::PENDING->value;
                $movement->investor_id = $investor->id;
                $movement->save();

                $deposit->movement_id = $movement->id;
                $deposit->save();

                // Notificación
                //$investor->sendDepositPendingEmailNotification($deposit);

                DB::commit();

                Log::info('Deposit@createDeposit - DB commit OK', [
                    'deposit_id'  => $deposit->id,
                    'movement_id' => $movement->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Operación creada correctamente.',
                    'data'    => ['deposit_url' => $urlTemporal],
                ], 201);
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Deposit@createDeposit - DB error, rolled back', [
                    'message' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Error al registrar el depósito: ' . $e->getMessage(),
                ], 500);
            }
        } catch (\Throwable $th) {
            Log::error('Deposit@createDeposit - FATAL', [
                'class'   => get_class($th),
                'message' => $th->getMessage(),
                'trace'   => $th->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function createWithdraw(StoreWithdrawRequest $request)
    {
        $validatedData = $request->validated();

        try {

            // Body values
            $bankID = $validatedData['bank'];
            $currency = $validatedData['currency'];
            $amount = MoneyConverter::fromDecimal($validatedData['amount'], $currency);

            $purpouse = $validatedData['purpouse'];

            DB::beginTransaction();

            /** @var \App\Models\Investor $investor */
            $investor = Auth::user();

            // TODO:  refactor this
            $bank_account = $investor->bankAccounts()
                ->where('id', $bankID)
                ->first();

            if (!$bank_account) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cuenta bancaria seleccionada no existe.',
                ], 404);
            }

            /**
             * Obtenemos el balance del inversor
             * Si el balance es negativo, no se puede realizar la operación
             * Si el balance es cero, no se puede realizar la operación
             * Si el balance es positivo, se puede realizar la operación
             * guardamos el balance
             */
            $balance = $investor->getBalance($currency);
            $balanceAmountMoney = MoneyConverter::fromDecimal($balance->amount, $currency);
            if ($balanceAmountMoney->isZero()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes saldo en tu billetera para realizar la operación',
                ], 400);
            }

            $balanceAmountMoney  = $balanceAmountMoney->subtract($amount);

            if ($balanceAmountMoney->isNegative()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes saldo suficiente en tu billetera para realizar la operación',
                ], 400);
            }
            $balance->amount = $balanceAmountMoney;
            $balance->save();


            // create new Movement
            $movement = new Movement();
            $movement->currency = $bank_account->currency;
            $movement->amount = $amount;
            $movement->type =  MovementType::WITHDRAW->value;
            $movement->investor_id = $investor->id;
            $movement->save();

            $withdrawal = new Withdraw();
            $withdrawal->currency = $bank_account->currency;
            $withdrawal->amount = $amount;
            $withdrawal->bank_account_id = $bank_account->id;
            $withdrawal->purpouse = $purpouse;
            $withdrawal->investor_id = $investor->id;
            $withdrawal->created_by = $investor->id;
            $withdrawal->updated_by = $investor->id;
            $withdrawal->movement_id = $movement->id;
            $withdrawal->save();


            // send email notification
            $investor->sendWithdrawalPendingEmailNotification($withdrawal);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Operación creada correctamente.',
                'data' => null,
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    public function createFixedRateDeposit(StoreFixedRateDepositRequest $request)
    {
        return $this->createGenericDeposit($request, 'tasas_fijas');
    }
    public function createMortgageDeposit(StoreMortgageDepositRequest $request)
    {
        return $this->createGenericDeposit($request, 'hipotecas');
    }
    public function createZumaDeposit(StoreZumaDepositRequest $request)
    {
        return $this->createGenericDeposit($request, 'zuma');
    }
    private function createGenericDeposit($request, string $type)
    {
        $validatedData = $request->validated();

        try {
            DB::beginTransaction();

            /** @var \App\Models\Investor $investor */
            $investor = Auth::user();
            $voucher = $validatedData['voucher'];
            $operationNumber = $validatedData['nro_operation'];
            $amount = $validatedData['amount'];
            $currency = 'PEN';

            $path = Storage::disk('s3')->putFileAs(
                'depositos',
                $voucher,
                Str::slug($investor->document) . '-' . Str::slug($operationNumber) . '.' . $voucher->extension()
            );

            $deposit = new Deposit();
            $deposit->nro_operation = $operationNumber;
            $deposit->currency = $currency;
            $deposit->amount = $amount;
            $deposit->resource_path = $path;
            $deposit->created_by = $investor->id;
            $deposit->updated_by = $investor->id;
            $deposit->investor_id = $investor->id;
            $deposit->payment_source = $validatedData['payment_source'] ?? null;
            $deposit->type = $type;

            if ($type === 'tasas_fijas' && isset($validatedData['fixed_term_investment_id'])) {
                $deposit->fixed_term_investment_id = $validatedData['fixed_term_investment_id'];
            }

            if ($type === 'hipotecas' && isset($validatedData['property_reservations_id'])) {
                $deposit->property_reservations_id = $validatedData['property_reservations_id'];
            }

            if ($type === 'zuma' && isset($validatedData['payment_schedules_id'])) {
                $deposit->payment_schedules_id = $validatedData['payment_schedules_id'];
            }

            $movement = new Movement();
            $movement->currency = $currency;
            $movement->amount = $amount;
            $movement->type = 'deposit';
            $movement->status = MovementStatus::PENDING->value;
            $movement->investor_id = $investor->id;
            $movement->description = $type;
            $movement->save();

            $deposit->movement_id = $movement->id;
            $deposit->save();

            $investor->sendDepositPendingEmailNotification($deposit);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Depósito registrado correctamente',
                'data' => null,
            ], 201);
        } catch (Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    public function validateMovement($id)
    {
        $movement = Movement::findOrFail($id);
        $movement->status = MovementStatus::VALID->value;
        $movement->save();
        return response()->json([
            'message' => 'Movimiento validado correctamente.',
            'data'    => $movement
        ], 200);
    }
    
    
    //detalle de movimiento
    public function detalleMovimientoDeposito($id){
        try {
            $token = PersonalAccessToken::findToken(request()->bearerToken());
            $investor = $token->tokenable;
            
            $deposito = Deposit::where('movement_id',$id)->where('investor_id',$investor->id)->with('bankAccount.bank')->first();
            
            return response()->json([
                'success' => true,
                'data' => $deposito->toArray(),
                'message' => null,
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], in_array((int)$th->getCode(), range(100,599)) ? (int)$th->getCode() : 500);
        }   
    }
    
    public function detalleMovimientoRetiro($id){
        try {
            $token = PersonalAccessToken::findToken(request()->bearerToken());
            $investor = $token->tokenable;
            
            $deposito = Withdraw::where('movement_id',$id)->where('investor_id',$investor->id)->with('bank_account.bank')->first();
            
            return response()->json([
                'success' => true,
                'data' => $deposito->toArray(),
                'message' => null,
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], in_array((int)$th->getCode(), range(100,599)) ? (int)$th->getCode() : 500);
        }   
    }
    
    public function detalleMovimientoExchangeUP($id){
        try {
            $token = PersonalAccessToken::findToken(request()->bearerToken());
            $investor = $token->tokenable;
            $investor_id = $investor->id;
            
            $data  = Movement::find($id);
            
            
            return response()->json([
                'success' => true,
                'data' => $data->toArray(),
                'message' => null,
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], in_array((int)$th->getCode(), range(100,599)) ? (int)$th->getCode() : 500);
        }   
    }
    
    public function detalleMovimientoExchangeDown($id){
        try {
            $token = PersonalAccessToken::findToken(request()->bearerToken());
            $investor = $token->tokenable;
            
            $deposito = Withdraw::where('movement_id',$id)->where('investor_id',$investor->id)->with('bank_account.bank')->first();
            
            return response()->json([
                'success' => true,
                'data' => $deposito->toArray(),
                'message' => null,
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], in_array((int)$th->getCode(), range(100,599)) ? (int)$th->getCode() : 500);
        }   
    }
    
    public function detalleMovimientoInversion($id){
        try {
            $token = PersonalAccessToken::findToken(request()->bearerToken());
            $investor = $token->tokenable;
            
            $deposito = Investment::with('invoice.company')
            ->where('movement_id', $id)
            ->where('investor_id', $investor->id)
            ->first();
            
            
            return response()->json([
                'success' => true,
                'data' => $deposito->toArray(),
                'message' => null,
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], in_array((int)$th->getCode(), range(100,599)) ? (int)$th->getCode() : 500);
        }   
    }
    
}
