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
use App\Models\Deposit;
use App\Models\Movement;
use Illuminate\Support\Str;
use App\Models\PaymentSchedule;
use App\Models\Property;
use App\Models\PropertyInvestor;
use App\Models\PropertyReservation;
use App\Models\Withdraw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MovementController extends Controller{
    public function listTasasFijas(Request $request){
        $movements = Movement::with('deposit')
            ->where('description', 'tasas_fijas')
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 10));

        return MovementResource::collection($movements)
            ->additional(['success' => true]);
    }
    public function listHipotecas(Request $request){
        $movements = Movement::with('deposit')
            ->where('description', 'hipotecas')
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 10));

        return MovementResource::collection($movements)
            ->additional(['success' => true]);
    }
    public function listPagosCliente(Request $request){
        $movements = Movement::with('deposit')
            ->where('description', 'zuma')
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 10));

        return MovementResource::collection($movements)
            ->additional(['success' => true]);
    }
    public function aceptarTasasFijas(string $id){
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
    public function rechazarTasasFijas(string $id){
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
    public function aceptarHipotecas(string $id){
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
    public function rechazarhipotecas(string $id){
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
    public function aceptarPagosCliente(string $id){
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
    public function rechazarPagosCliente(string $id){
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
    public function index(Request $request){
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
                    'investment.invoice.company' // Agregamos la relación con company
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
    public function createDeposit(StorePaymentsRequest $request){
        $validatedData = $request->validated();

        try {
            // Valores del body
            $bankID = $validatedData['bank'];
            $amount = $validatedData['amount'];
            $operationNumber = $validatedData['nro_operation'];
            $voucher = $validatedData['voucher'];

            /** @var \App\Models\Investor $investor */
            $investor = Auth::user();

            // Buscar la cuenta bancaria del inversor
            $bank_account = $investor->bankAccounts()
                ->where('id', $bankID)
                ->first();

            if (!$bank_account) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cuenta bancaria seleccionada no existe.',
                ], 404);
            }

            // Validar el archivo
            if (!$voucher || !$voucher->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo no es válido.',
                ], 400);
            }

            // Generar nombre de archivo
            $fileName = Str::slug($investor->document) . '-' . Str::slug($operationNumber) . '.' . $voucher->getClientOriginalExtension();

            // Preparar disco S3
            /** @var \Illuminate\Filesystem\AwsS3FilesystemAdapter $disk */
            $disk = Storage::disk('s3');

            // Subir archivo a S3 en carpeta "depositos"
            try {
                $path = $disk->putFileAs('depositos', $voucher, $fileName);

                if (!$path) {
                    Log::error('File upload failed - putFileAs returned false', [
                        'fileName' => $fileName,
                        'folder' => 'depositos',
                        'fileSize' => $voucher->getSize(),
                        'mimeType' => $voucher->getMimeType()
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Error al subir el archivo. Verifique la configuración de almacenamiento.',
                    ], 500);
                }

                Log::info('File uploaded successfully', ['path' => $path]);
            } catch (\Exception $e) {
                Log::error('File upload exception', [
                    'error' => $e->getMessage(),
                    'fileName' => $fileName,
                    'fileSize' => $voucher->getSize()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Error al subir el archivo: ' . $e->getMessage(),
                ], 500);
            }

            // Generar URL temporal (expira en 60 minutos)
            try {
                $urlTemporal = $disk->temporaryUrl(
                    'depositos/' . $fileName,
                    now()->addMinutes(60)
                );
            } catch (\Exception $e) {
                Log::error('Error generating temporary URL', ['error' => $e->getMessage()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error al generar URL temporal: ' . $e->getMessage(),
                ], 500);
            }

            DB::beginTransaction();

            // Crear depósito
            $deposit = new Deposit();
            $deposit->nro_operation = $operationNumber;
            $deposit->currency = $bank_account->currency;
            $deposit->amount = $amount;
            $deposit->resource_path = $path; // Guardar path real
            $deposit->created_by = $investor->id;
            $deposit->updated_by = $investor->id;
            $deposit->investor_id = $investor->id;
            $deposit->bank_account_id = $bank_account->id;

            // Crear movimiento
            $movement = new Movement();
            $movement->currency = $bank_account->currency;
            $movement->amount = $amount;
            $movement->type = 'deposit';
            $movement->status = MovementStatus::PENDING->value;
            $movement->investor_id = $investor->id;
            $movement->save();

            $deposit->movement_id = $movement->id;
            $deposit->save();

            // Enviar notificación
            $investor->sendDepositPendingEmailNotification($deposit);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Operación creada correctamente.',
                'data' => [
                    'deposit_url' => $urlTemporal
                ],
            ], 201);

        } catch (Throwable $th) {
            DB::rollBack();
            Log::error('Deposit creation failed', ['error' => $th->getMessage(), 'trace' => $th->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function createWithdraw(StoreWithdrawRequest $request){
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
    public function createFixedRateDeposit(StoreFixedRateDepositRequest $request){
        return $this->createGenericDeposit($request, 'tasas_fijas');
    }
    public function createMortgageDeposit(StoreMortgageDepositRequest $request){
        return $this->createGenericDeposit($request, 'hipotecas');
    }
    public function createZumaDeposit(StoreZumaDepositRequest $request){
        return $this->createGenericDeposit($request, 'zuma');
    }
    private function createGenericDeposit($request, string $type){
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
    public function validateMovement($id){
        $movement = Movement::findOrFail($id);
        $movement->status = MovementStatus::VALID->value;
        $movement->save();
        return response()->json([
            'message' => 'Movimiento validado correctamente.',
            'data'    => $movement
        ], 200);
    }
}
