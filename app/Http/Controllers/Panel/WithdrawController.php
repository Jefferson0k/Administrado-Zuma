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
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;
use App\Models\HistoryAprobadorWithdraw;

class WithdrawController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Withdraw::class);
        $withdraws = Withdraw::latest()->paginate(10);
        return response()->json(WithdrawResource::collection($withdraws));
    }

    public function show($investorId)
    {
        Gate::authorize('viewAny', Withdraw::class);
        $investor = Investor::with([
            'movements.deposit',
            'movements.withdraw',
            'movements.investment',
        ])->findOrFail($investorId);
        $movements = $investor->movements()
            ->where(function ($q) {
                // deposits: only approved
                $q->whereHas('deposit', function ($dq) {
                    $dq->where('status_conclusion', 'approved');
                })
                    // withdraws: only valid
                    ->orWhereHas('withdraw', function ($wq) {
                        $wq->where('status', 'approved');
                    })
                    // investments: keep all
                    ->orWhereHas('investment');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return WithdrawMovementResource::collection($movements)
            ->additional([
                'investor' => new WithdrawInvestorResource($investor),
            ]);
    }

    // public function store(StoreWithdrawRequests $request)
    // {
    //     Gate::authorize('create', Withdraw::class);
    //     $withdraw = Withdraw::create($request->validated());
    //     return response()->json([
    //         'message' => 'Retiro creado correctamente',
    //         'data' => new WithdrawResource($withdraw),
    //     ]);
    // }

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

    // public function destroy($id)
    // {
    //     $withdraw = Withdraw::findOrFail($id);
    //     Gate::authorize('delete', $withdraw);
    //     $withdraw->delete();
    //     return response()->json([
    //         'message' => 'Retiro eliminado correctamente',
    //     ]);
    // }
    public function uploadVoucher(Request $request, $id)
    {
        $withdraw = Withdraw::findOrFail($id);
        Gate::authorize('uploadFiles', $withdraw);

        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'comment' => 'nullable|string|max:2000',
        ]);

        try {
            $path = $request->file('file')->store('retiros', 's3');

            $withdraw->update([
                'resource_path'    => $path,
                'payment_comment'  => $request->input('comment'), // 👈 guarda el comentario de pago
            ]);

            return response()->json([
                'message' => 'Archivo subido correctamente',
                'data'    => new WithdrawResource($withdraw->fresh()),
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'message' => 'Error al subir el archivo',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    public function approveStepOne(Request $request, $id)
    {
        $withdraw = Withdraw::findOrFail($id);
        Gate::authorize('approve1', $withdraw);
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
                'approval2_status' => null,
                'approval1_by'     => Auth::id(),
                'approval1_comment' => $request->input('approval1_comment'),
                'approval1_at'     => now(),
            ]);

            $withdraw->movement()->update([
                'status' => 'valid',
            ]);


            HistoryAprobadorWithdraw::create([
                'withdraw_id' => $withdraw->id,
                'approval1_status' => 'approved',
                'approval1_by' => Auth::id(),
                'approval1_comment' => $request->input('approval1_comment'),
                'approval1_at' => now(),
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
    public function approveStepTwo(Request $request, $id)
    {
        $withdraw = Withdraw::findOrFail($id);
        $movement = Movement::findOrFail($withdraw->movement_id);
        Gate::authorize('approve2', $withdraw);
        $request->validate([
            'approval2_comment' => 'nullable|string',
        ]);
        try {
            DB::beginTransaction();
            $withdraw->update([
                'status'       => 'approved',
                'approval2_status' => 'approved',
                'approval2_by'     => Auth::id(),
                'approval2_comment' => $request->input('approval2_comment'),
                'approval2_at'     => now(),
            ]);
            $movement->confirm_status = MovementStatus::VALID->value;
            $movement->save();
            $withdraw->investor->sendWithdrawApprovedEmailNotification($withdraw);



            $history = HistoryAprobadorWithdraw::where('withdraw_id', $withdraw->id)
                ->latest()
                ->first();

            $history?->update([
                'approval2_status' => 'approved',
                'approval2_by' => Auth::id(),
                'approval2_comment' => $request->input('approval2_comment'),
                'approval2_at' => now(),
            ]);
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



    public function observeStepOne(Request $request, $id)
    {
        $withdraw = Withdraw::findOrFail($id);
        Gate::authorize('approve1', $withdraw);

        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $withdraw->update([
                'approval1_status'  => 'observed',
                'approval1_by'      => Auth::id(),
                'approval1_comment' => $validated['comment'],
                'approval1_at'      => now(),
            ]);

            // Si manejas estado en Movement, podrías marcarlo como "observed" aquí (opcional)
            // $withdraw->movement?->update(['status' => 'observed']);


            HistoryAprobadorWithdraw::create([
                'withdraw_id' => $withdraw->id,
                'approval1_status' => 'observed',
                'approval1_by' => Auth::id(),
                'approval1_comment' => $request->input('approval1_comment'),
                'approval1_at' => now(),
            ]);



            DB::commit();

            return response()->json([
                'message' => 'Retiro observado en primera validación',
                'data'    => new WithdrawResource($withdraw->fresh()),
            ]);
        } catch (Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al observar en primera validación',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    public function observeStepTwo(Request $request, $id)
    {
        $withdraw = Withdraw::findOrFail($id);
        Gate::authorize('approve2', $withdraw);

        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $withdraw->update([
                'approval2_status'  => 'observed',
                'approval2_by'      => Auth::id(),
                'approval2_comment' => $validated['comment'],
                'approval2_at'      => now(),
                // no tocar status global ni enviar correo
            ]);

            // $withdraw->movement?->update(['status' => 'observed']); // opcional

            $history = HistoryAprobadorWithdraw::where('withdraw_id', $withdraw->id)
                ->latest()
                ->first();

            $history?->update([
                'approval2_status' => 'observed',
                'approval2_by' => Auth::id(),
                'approval2_comment' => $request->input('approval2_comment'),
                'approval2_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Retiro observado en segunda validación',
                'data'    => new WithdrawResource($withdraw->fresh()),
            ]);
        } catch (Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al observar en segunda validación',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }


    public function rejectStepOne(Request $request, $id)
    {
        $withdraw = Withdraw::findOrFail($id);
        Gate::authorize('approve1', $withdraw);

        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $withdraw->update([
                'approval1_status'  => 'rejected',
                'approval1_by'      => Auth::id(),
                'approval1_comment' => $validated['comment'],
                'approval1_at'      => now(),
                // estado global del retiro (si lo usas)
                'status'            => 'rejected',
            ]);

            // Si manejas MovementStatus de rechazo, actualízalo (opcional)
            // $withdraw->movement?->update(['status' => 'rejected']);

            HistoryAprobadorWithdraw::create([
                'withdraw_id' => $withdraw->id,
                'approval1_status' => 'rejected',
                'approval1_by' => Auth::id(),
                'approval1_comment' => $request->input('approval1_comment'),
                'approval1_at' => now(),
            ]);



            DB::commit();

            return response()->json([
                'message' => 'Retiro rechazado en primera validación',
                'data'    => new WithdrawResource($withdraw->fresh()),
            ]);
        } catch (Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al rechazar en primera validación',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }



    public function rejectStepTwo(Request $request, $id)
    {
        $withdraw = Withdraw::findOrFail($id);
        Gate::authorize('approve2', $withdraw);

        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $withdraw->update([
                'approval2_status'  => 'rejected',
                'approval2_by'      => Auth::id(),
                'approval2_comment' => $validated['comment'],
                'approval2_at'      => now(),
                'status'            => 'rejected',
            ]);

            // Si usas enum MovementStatus con REJECTED, actualízalo:
            // $movement = Movement::find($withdraw->movement_id);
            // if ($movement) {
            //     $movement->confirm_status = MovementStatus::REJECTED->value;
            //     $movement->save();
            // }


            $history = HistoryAprobadorWithdraw::where('withdraw_id', $withdraw->id)
                ->latest()
                ->first();

            $history?->update([
                'approval2_status' => 'rejected',
                'approval2_by' => Auth::id(),
                'approval2_comment' => $request->input('approval2_comment'),
                'approval2_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Retiro rechazado en segunda validación',
                'data'    => new WithdrawResource($withdraw->fresh()),
            ]);
        } catch (Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al rechazar en segunda validación',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }


    public function pay(Request $request, $id)
    {
        $withdraw = Withdraw::findOrFail($id);
        Gate::authorize('pay', $withdraw);

        // Debe estar aprobado en segunda validación
        if (!($withdraw->status === 'approved' && $withdraw->approval2_status === 'approved')) {
            return response()->json([
                'message' => 'El retiro debe estar aprobado en segunda validación antes de registrar el pago.',
            ], 422);
        }

        $validated = $request->validate([
            'payment_comment' => ['nullable', 'string', 'max:2000'],
            'file'         => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ]);

        try {
            DB::beginTransaction();

            // Subir file
            $path = $request->file('file')->store('retiros/vouchers', 's3');

            // Actualizar solo los campos solicitados
            $withdraw->update([
                'payment_comment'      => $validated['payment_comment'] ?? null,
                'resource_path' => $path,
                // Si ya tienes columnas de pago y quieres marcarlas aquí, descomenta:
                // 'paid_status' => 'paid',
                // 'paid_by'     => Auth::id(),
                // 'paid_at'     => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Pago registrado correctamente',
                'data'    => new WithdrawResource($withdraw->fresh()),
            ]);
        } catch (\Throwable $th) {
            Log::error('Error al registrar el pago: ' . $th->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar el pago',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }




    public function approvalHistory($id)
    {
        $rows = HistoryAprobadorWithdraw::query()
            ->where('withdraw_id', $id) // <- corregido: withdraw_id
            ->with([
                'approval1By:id,name',
                'approval2By:id,name',
            ])
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }
}
