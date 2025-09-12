<?php
namespace App\Http\Controllers\Panel;

use App\Enums\MovementStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Withdraw\StoreWithdrawRequests;
use App\Http\Requests\Withdraw\UpdateWithdrawRequest;
use App\Http\Resources\Factoring\Withdraw\WithdrawResource;
use App\Models\Movement;
use App\Models\Withdraw;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class WithdrawController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Withdraw::class);
        $withdraws = Withdraw::latest()->paginate(10);
        return response()->json(WithdrawResource::collection($withdraws));
    }

    public function show($id)
    {
        $withdraw = Withdraw::findOrFail($id);
        Gate::authorize('view', $withdraw);
        return response()->json(new WithdrawResource($withdraw));
    }

    public function store(StoreWithdrawRequests $request)
    {
        Gate::authorize('create', Withdraw::class);
        $withdraw = Withdraw::create($request->validated());
        return response()->json([
            'message' => 'Retiro creado correctamente',
            'data' => new WithdrawResource($withdraw),
        ]);
    }

    public function update(UpdateWithdrawRequest $request, $id)
    {
        $withdraw = Withdraw::findOrFail($id);
        Gate::authorize('update', $withdraw);
        $withdraw->update($request->validated());
        return response()->json([
            'message' => 'Retiro actualizado correctamente',
            'data' => new WithdrawResource($withdraw),
        ]);
    }

    public function destroy($id)
    {
        $withdraw = Withdraw::findOrFail($id);
        Gate::authorize('delete', $withdraw);
        $withdraw->delete();
        return response()->json([
            'message' => 'Retiro eliminado correctamente',
        ]);
    }

    // Método corregido - removí el parámetro $movementId ya que se obtiene del withdraw
    public function approve(StoreWithdrawRequests $request, $id)
    {
        $withdraw = Withdraw::findOrFail($id);
        $movement = Movement::findOrFail($withdraw->movement_id); // Obtenemos el movement del withdraw
        
        Gate::authorize('update', $withdraw);

        try {
            DB::beginTransaction();

            // Validar que el archivo fue enviado
            if (!$request->hasFile('file')) {
                return response()->json([
                    'message' => 'El archivo voucher es requerido',
                ], 422);
            }

            // Subir el archivo
            $filePath = $request->file('file')->store('retiros', 's3');

            // Actualizar el withdraw
            $withdraw->update([
                'nro_operation' => $request->nro_operation,
                'deposit_pay_date' => $request->deposit_pay_date,
                'resource_path' => $filePath,
                'description' => $request->description,
            ]);

            // Actualizar el status del movement
            $movement->status = MovementStatus::VALID->value;
            $movement->save();

            // Enviar notificación por email
            $withdraw->investor->sendWithdrawApprovedEmailNotification($withdraw);

            DB::commit();

            return response()->json([
                'message' => 'Retiro aprobado correctamente',
                'data' => new WithdrawResource($withdraw->fresh()), // fresh() para obtener los datos actualizados
            ]);

        } catch (Throwable $th) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Error al aprobar el retiro',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}