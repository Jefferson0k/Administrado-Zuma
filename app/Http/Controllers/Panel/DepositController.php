<?php

namespace App\Http\Controllers\Panel;

use App\Enums\MovementStatus;
use App\Helpers\MoneyConverter;
use App\Http\Controllers\Controller;
use App\Http\Resources\Factoring\Deposit\DepositResource;
use App\Models\Deposit;
use App\Models\Investor;
use App\Models\Movement;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class DepositController extends Controller{
    public function index(){
        try {
            Gate::authorize('viewAny', Deposit::class);
            $depost = Deposit::all();
            return response()->json([
                'total' => $depost->count(),
                'data'  => DepositResource::collection($depost),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'No tienes permiso para ver los depositos.'
            ], 403);
        }catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al listar las inversiones.',
                'error'   => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ], 500);
        }
    }
    public function show($id){
        try {
            $deposit = Deposit::findOrFail($id);
            Gate::authorize('view', $deposit);
            return response()->json([
                'data' => new DepositResource($deposit)
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'No tienes permiso para ver este depósito.'
            ], 403);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Depósito no encontrado.'
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al obtener el depósito.',
                'error'   => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ], 500);
        }
    }
    public function validateDeposit($movementId){
        $movement = Movement::findOrFail($movementId);
        if ($movement->status !== MovementStatus::PENDING) {
            return response()->json(['message' => 'El movimiento ya fue procesado'], 400);
        }
        $movement->status = MovementStatus::VALID;
        $movement->confirm_status = MovementStatus::PENDING;
        $movement->registrarAprobacion1(Auth::id());
        $movement->save();
        $deposit = Deposit::where('movement_id', $movementId)->first();
        if ($deposit) {
            $deposit->update([
                'aprobacion_1' => now(),
                'aprobado_por_1' => Auth::id(),
                'estado' => 'valid'
            ]);
        }
        return response()->json(['message' => 'Depósito validado correctamente']);
    }
    public function rejectDeposit($depositId, $movementId){
        $movement = Movement::findOrFail($movementId);
        if ($movement->status !== 'pending') {
            return response()->json(['message' => 'El movimiento ya fue procesado'], 400);
        }
        $movement->status = 'rejected';
        $movement->confirm_status = 'rejected';
        $movement->save();
        $deposit = Deposit::findOrFail($depositId);
        $deposit->update([
            'estado' => 'rejected',
            'estadoConfig' => 'rejected'
        ]);
        return response()->json(['message' => 'Depósito rechazado correctamente']);
    }
    public function approveDeposit($depositId, $movementId){
        try {
            DB::beginTransaction();
            $movement = Movement::findOrFail($movementId);
            if ($movement->status !== MovementStatus::VALID ||
                $movement->confirm_status === MovementStatus::VALID) {
                return response()->json(['message' => 'El movimiento no es válido para aprobar'], 400);
            }
            $movement->confirm_status = MovementStatus::VALID;
            $movement->registrarAprobacion2(Auth::id());
            $investor = Investor::findOrFail($movement->investor_id);
            $balance = $investor->getBalance($movement->currency);
            $balanceAmountMoney = MoneyConverter::fromDecimal($balance->amount, $balance->currency);
            $movementAmountMoney = MoneyConverter::fromDecimal($movement->amount, $movement->currency);
            $balance->amount = $balanceAmountMoney->add($movementAmountMoney);
            $balance->save();
            $deposit = Deposit::findOrFail($depositId);
            $deposit->update([
                'aprobacion_2' => now(),
                'aprobado_por_2' => Auth::id(),
                'estadoConfig' => 'confirmed'
            ]);
            $investor->sendDepositApprovalEmailNotification($deposit);
            $movement->save();
            DB::commit();
            return response()->json(['message' => 'Depósito aprobado y saldo actualizado']);
        } catch (Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al aprobar el depósito',
                'error' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ], 500);
        }
    }
    public function rejectConfirmDeposit($depositId, $movementId){
        $movement = Movement::findOrFail($movementId);
        if ($movement->status !== MovementStatus::VALID ||
            $movement->confirm_status !== MovementStatus::PENDING) {
            return response()->json(['message' => 'El movimiento no es válido para rechazar'], 400);
        }
        $movement->confirm_status = MovementStatus::REJECTED;
        $movement->save();
        $deposit = Deposit::findOrFail($depositId);
        $deposit->update([
            'estadoConfig' => 'rejected'
        ]);
        return response()->json(['message' => 'Depósito rechazado en confirmación']);
    }
}
