<?php
namespace App\Http\Controllers\Panel;

use App\Enums\MovementStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Withdraw\StoreWithdrawRequests;
use App\Http\Requests\Withdraw\UpdateWithdrawRequest;
use App\Http\Resources\Factoring\Withdraw\WithdrawInvestorResource;
use App\Http\Resources\Factoring\Withdraw\WithdrawMovementResource;
use App\Http\Resources\Factoring\Withdraw\WithdrawResource;
use App\Models\Investor;
use App\Models\Movement;
use App\Models\Withdraw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function show($investorId){
        $investor = Investor::with([
            'movements.deposit',
            'movements.withdraw',
            'movements.investment',
        ])->findOrFail($investorId);
        $movements = $investor->movements()
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return WithdrawMovementResource::collection($movements)
            ->additional([
                'investor' => new WithdrawInvestorResource($investor),
            ]);
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
    public function uploadVoucher(Request $request, $id){
        $withdraw = Withdraw::findOrFail($id);
        Gate::authorize('update', $withdraw);

        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        try {
            $filePath = $request->file('file')->store('retiros', 's3');
            $withdraw->update([
                'resource_path' => $filePath,
            ]);

            return response()->json([
                'message' => 'Archivo subido correctamente',
                'file_path' => $filePath,
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'message' => 'Error al subir el archivo',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }
    public function approveStepOne(Request $request, $id){
        $withdraw = Withdraw::findOrFail($id);
        Gate::authorize('update', $withdraw);
        $request->validate([
            'nro_operation'    => 'required|string|max:50',
            'deposit_pay_date' => 'required|date',
            'description'      => 'nullable|string',
        ]);
        try {
            DB::beginTransaction();
            $withdraw->update([
                'nro_operation'    => $request->nro_operation,
                'deposit_pay_date' => $request->deposit_pay_date,
                'description'      => $request->description,
                'approval1_status' => 'approved',
                'approval1_by'     => Auth::id(),
                'approval1_comment'=> $request->input('approval1_comment'),
                'approval1_at'     => now(),
            ]);
            DB::commit();
            return response()->json([
                'message' => 'Primera validación realizada correctamente',
                'data'    => new WithdrawResource($withdraw->fresh()),
            ]);
        } catch (Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error en la primera validación',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }
    public function approveStepTwo(Request $request, $id){
        $withdraw = Withdraw::findOrFail($id);
        $movement = Movement::findOrFail($withdraw->movement_id);
        Gate::authorize('update', $withdraw);
        $request->validate([
            'approval2_comment' => 'nullable|string',
        ]);
        try {
            DB::beginTransaction();
            $withdraw->update([
                'status'       => 'approved',
                'approval2_status' => 'approved',
                'approval2_by'     => Auth::id(),
                'approval2_comment'=> $request->input('approval2_comment'),
                'approval2_at'     => now(),
            ]);
            $movement->status = MovementStatus::VALID->value;
            $movement->save();
            $withdraw->investor->sendWithdrawApprovedEmailNotification($withdraw);
            DB::commit();
            return response()->json([
                'message' => 'Retiro aprobado en segunda validación correctamente',
                'data'    => new WithdrawResource($withdraw->fresh()),
            ]);
        } catch (Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error en la segunda validación',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }
}