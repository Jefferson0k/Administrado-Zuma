<?php

namespace App\Http\Controllers\Api;

use App\Enums\Currency;
use App\Enums\MovementStatus;
use App\Enums\MovementType;
use App\Helpers\MoneyConverter;
use App\Http\Controllers\Controller;
use App\Models\Exchange;
use App\Models\Movement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\ExchangeType;
use App\Models\ExchangeTypeMovement;

class ExchangeControllerFonted extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $exchanges = Exchange::where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->first();

            return response()->json([
                'success' => true,
                'data' => $exchanges,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Exchange $exchange)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Exchange $exchange)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Exchange $exchange)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Exchange $exchange)
    {
        //
    }

    /**
     * Exchange USD to PEN
     *
     * Convierte moneda USD (Dólares) a PEN (Soles) usando la tasa de cambio activa.
     * El proceso incluye:
     * - Validación del monto a convertir
     * - Obtención de la tasa de cambio activa
     * - Deducción del monto de la billetera USD
     * - Adición del monto convertido a la billetera PEN
     * - Registro de movimientos de transacción
     *
     * 
     * @OA\Post(
     *     path="/exchange/usd-to-pen",
     *     summary="Convertir USD a PEN",
     *     description="Convierte moneda USD (Dólares) a PEN (Soles) usando la tasa de cambio activa. El proceso incluye validación del monto, obtención de la tasa de cambio, actualización de balances y registro de movimientos.",
     *     tags={"Tipo de cambio"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(
     *                 property="amount", 
     *                 type="number", 
     *                 format="float",
     *                 example=100.00, 
     *                 description="Monto en USD (Dólares) a convertir. Debe ser mayor a 0.",
     *                 minimum=0.01
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Conversión exitosa",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data", 
     *                 type="object",
     *                 @OA\Property(
     *                     property="amount", 
     *                     type="number", 
     *                     format="float",
     *                     example=100.00, 
     *                     description="Monto original en USD"
     *                 ),
     *                 @OA\Property(
     *                     property="rate", 
     *                     type="number", 
     *                     format="float",
     *                     example=3.75, 
     *                     description="Tasa de cambio utilizada (USD/PEN)"
     *                 ),
     *                 @OA\Property(
     *                     property="total", 
     *                     type="number", 
     *                     format="float",
     *                     example=375.00, 
     *                     description="Monto convertido en PEN"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="message", 
     *                 type="string", 
     *                 example="El campo monto debe ser mayor a cero."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="message", 
     *                 type="string", 
     *                 example="Unauthenticated."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tasa de cambio no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="message", 
     *                 type="string", 
     *                 example="Exchange not found"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Saldo insuficiente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="message", 
     *                 type="string", 
     *                 example="Saldo insuficiente en la billetera USD"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="message", 
     *                 type="string", 
     *                 example="Error interno del servidor"
     *             )
     *         )
     *     )
     * )
     */
    public function usdToPen(Request $request)
    {
        $validatedData = $request->validate([
            'amount' => 'required|numeric|gt:0',
        ], [
            'amount.required' => 'El campo monto es obligatorio.',
            'amount.numeric' => 'El campo monto debe ser un número.',
            'amount.gt' => 'El campo monto debe ser mayor a cero.',
        ]);

        $amount = MoneyConverter::fromDecimal((float) $validatedData['amount'], Currency::USD->value);

        try {

            $exchange =  Exchange::where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->first();

            /**
             * @var \App\Models\Exchange $exchange
             * En este bloque se obtiene el exchange activo
             * si no existe, se retorna un error 404
             */
            if (is_null($exchange)) return response()->json([
                'success' => false,
                'message' => 'Exchange not found',
            ], 404);

            $rate = floatval($exchange->exchange_rate_buy);
            $result = $exchange->usdToPen($amount);

            // in the next line, we are saving the current investor's balance
            /** @var \App\Models\Investor $investor */
            $investor = Auth::user();

            /**
             * Empezar la transacción
             * Si ocurre un error, se revierte la transacción
             */
            DB::beginTransaction();
            
            /**
             * @var \App\Models\Balance $walletUsd
             * En este bloque se resta el monto de la billetera de USD
             */
            $walletUsd = $investor->getBalance(Currency::USD->value);
            $walletUsd->subtractAmount($amount);
            $walletUsd->save();

            /**
             * @var \App\Models\Movement $movement
             * Se crea el movimiento de la billetera de USD
             */
            $movementUsd = new Movement();
            $movementUsd->currency = Currency::USD;
            $movementUsd->amount = $amount;
            $movementUsd->type = MovementType::EXCHANGE_DOWN;
            $movementUsd->status = MovementStatus::VALID;
            $movementUsd->confirm_status = MovementStatus::VALID;
            $movementUsd->investor_id = $investor->id;
            $movementUsd->save();
            
            //$movement = null;

            /**
             * @var \App\Models\Balance $walletPen
             * En este bloque se agrega el monto a la billetera de PEN
             */
            $walletPen = $investor->getBalance(Currency::PEN->value);
            $walletPen->addAmount($result);
            $walletPen->save();


            /**
             * @var \App\Models\Movement $movement
             * Se crea el movimiento de la billetera de PEN
             */
            $movementPen = new Movement();
            $movementPen->currency = Currency::PEN;
            $movementPen->amount = $result;
            $movementPen->type = MovementType::EXCHANGE_UP;
            $movementPen->status = MovementStatus::VALID;
            $movementPen->confirm_status = MovementStatus::VALID;
            $movementPen->investor_id = $investor->id;
            $movementPen->save();
            
            
         
            
            // in the next line, we are sending a notification to the investor
            DB::commit();
            return response()->json([
                'success' => true,
                'data' => [
                    'amount' => MoneyConverter::getValue($amount),
                    'rate' => $rate,
                    'total' => MoneyConverter::getValue($result),
                ],
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Exchange PEN to USD
     *
     * Convierte moneda PEN (Soles) a USD (Dólares) usando la tasa de cambio activa.
     * El proceso incluye:
     * - Validación del monto a convertir
     * - Obtención de la tasa de cambio activa
     * - Deducción del monto de la billetera PEN
     * - Adición del monto convertido a la billetera USD
     * - Registro de movimientos de transacción
     *
     * 
     * @OA\Post(
     *     path="/exchange/pen-to-usd",
     *     summary="Convertir PEN a USD",
     *     description="Convierte moneda PEN (Soles) a USD (Dólares) usando la tasa de cambio activa. El proceso incluye validación del monto, obtención de la tasa de cambio, actualización de balances y registro de movimientos.",
     *     tags={"Tipo de cambio"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(
     *                 property="amount", 
     *                 type="number", 
     *                 format="float",
     *                 example=1000.50, 
     *                 description="Monto en PEN (Soles) a convertir. Debe ser mayor a 0.",
     *                 minimum=0.01
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Conversión exitosa",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data", 
     *                 type="object",
     *                 @OA\Property(
     *                     property="amount", 
     *                     type="string", 
     *                     example="1000.50", 
     *                     description="Monto original en PEN"
     *                 ),
     *                 @OA\Property(
     *                     property="rate", 
     *                     type="number", 
     *                     format="float",
     *                     example=3.75, 
     *                     description="Tasa de cambio utilizada (PEN/USD)"
     *                 ),
     *                 @OA\Property(
     *                     property="total", 
     *                     type="string", 
     *                     example="266.80", 
     *                     description="Monto convertido en USD"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="message", 
     *                 type="string", 
     *                 example="El campo monto debe ser mayor a cero."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="message", 
     *                 type="string", 
     *                 example="Unauthenticated."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tasa de cambio no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="message", 
     *                 type="string", 
     *                 example="Exchange not found"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Saldo insuficiente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="message", 
     *                 type="string", 
     *                 example="Saldo insuficiente en la billetera PEN"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="message", 
     *                 type="string", 
     *                 example="Error interno del servidor"
     *             )
     *         )
     *     )
     * )
     */
    public function penToUsd(Request $request)
    {
        $validatedData = $request->validate([
            'amount' => 'required|numeric|gt:0',
        ], [
            'amount.required' => 'El campo monto es requerido.',
            'amount.numeric' => 'El campo monto debe ser un número.',
            'amount.gt' => 'El campo monto debe ser mayor a cero.',
        ]);

        $amount = MoneyConverter::fromDecimal((float) $validatedData['amount'], Currency::PEN->value);

        try {
            $exchange =  Exchange::where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->first();

            /**
             * @var \App\Models\Exchange $exchange
             * En este bloque se obtiene el exchange activo
             * si no existe, se retorna un error 404
             */
            if (is_null($exchange)) return response()->json([
                'success' => false,
                'message' => 'Exchange not found',
            ], 404);

            $rate = floatval($exchange->exchange_rate_sell);
            $result = $exchange->penToUsd($amount);


            // in the next line, we are saving the current investor's balance
            /** @var \App\Models\Investor $investor */
            $investor = Auth::user();

            /**
             * Empezar la transacción
             * Si ocurre un error, se revierte la transacción
             */
            DB::beginTransaction();

            /**
             * @var \App\Models\Balance $walletPen
             * En este bloque se resta el monto de la billetera de PEN
             */
            $walletPen = $investor->getBalance(Currency::PEN->value);
            $walletPen->subtractAmount($amount);
            $walletPen->save();

            /**
             * @var \App\Models\Movement $movement
             * Se crea el movimiento de la billetera de PEN
             */
            $movement = new Movement();
            $movement->currency = Currency::PEN;
            $movement->amount = $amount;
            $movement->type = MovementType::EXCHANGE_DOWN;
            $movement->status = MovementStatus::VALID;
            $movement->confirm_status = MovementStatus::VALID;
            $movement->investor_id = $investor->id;
            $movement->save();
            $movement = null;

            /**
             * @var \App\Models\Balance $walletUsd
             * En este bloque se agrega el monto a la billetera de USD
             */
            $walletUsd = $investor->getBalance(Currency::USD->value);
            $walletUsd->addAmount($result);
            $walletUsd->save();

            /**
             * @var \App\Models\Movement $movement
             * Se crea el movimiento de la billetera de USD
             */
            $movement = new Movement();
            $movement->currency = Currency::USD;
            $movement->amount = $result;
            $movement->type = MovementType::EXCHANGE_UP;
            $movement->status = MovementStatus::VALID;
            $movement->confirm_status = MovementStatus::VALID;
            $movement->investor_id = $investor->id;
            $movement->save();

            // in the next line, we are sending a notification to the investor

            DB::commit();
            return response()->json([
                'success' => true,
                'data' => [
                    'amount' => MoneyConverter::getValue($amount),
                    'rate' => $rate,
                    'total' => MoneyConverter::getValue($result),
                ],
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => "Error interno del servidor",
            ], 500);
        }
    }
}
