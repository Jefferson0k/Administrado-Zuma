<?php

namespace App\Http\Controllers\Panel;

use App\Enums\MovementStatus;
use App\Exports\DepositsExport;
use App\Helpers\MoneyConverter;
use App\Http\Controllers\Controller;
use App\Http\Resources\Factoring\Deposit\DepositResource;
use App\Models\Deposit;
use App\Models\Investor;
use App\Models\Movement;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;
use Illuminate\Support\Facades\Log;
use App\Models\DepositAttachment;
use Illuminate\Support\Facades\Storage;


class DepositController extends Controller
{
    public function index()
    {
        try {
            Gate::authorize('viewAny', Deposit::class);
            $primarySort = Schema::hasColumn('deposits', 'deposit_date') ? 'deposit_date'
                : (Schema::hasColumn('deposits', 'date') ? 'date'
                    : (Schema::hasColumn('deposits', 'created_at') ? 'created_at' : 'id'));
            $deposits = Deposit::query()
                ->orderByDesc($primarySort)
                ->orderByDesc('id')
                ->get();

            return response()->json([
                'total' => $deposits->count(),
                'data'  => DepositResource::collection($deposits),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'No tienes permiso para ver los depósitos.'
            ], 403);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al listar los depósitos.',
                'error'   => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ], 500);
        }


        
    }



    public function updateStatus0(string $id, Request $request)
    {
        $validated = $request->validate([
            'status0'  => 'required|in:approved,observed,rejected,pending',
            'comment0' => 'nullable|string|max:1000',
            'notify_message' => 'nullable|string|max:1000',
        ]);

        $deposit = Deposit::findOrFail($id);
        Gate::authorize('update', $deposit);

        // Exigir voucher si quieren aprobar en la 1ª validación
        if ($validated['status0'] === 'approved' && !$deposit->attachments()->exists()) {
            return response()->json([
                'message' => 'Debes adjuntar y subir el voucher antes de aprobar la primera validación.'
            ], 422);

        }


        // UI → DB (approved|observed|pending|rejected → valid|invalid|pending|rejected)
        $dbStatus0 = DepositResource::uiToDb($validated['status0']);

        // Guardar comentario y auditoría en el depósito
        if (!empty($validated['comment0'])) {
            $deposit->comment0 = $validated['comment0'];
        }
        $deposit->updated_by = Auth::id();
        $deposit->save();

        // Actualizar 1ª validación en Movement y resetear SIEMPRE la 2ª a pending
        $movement = $deposit->movement;
        $movement->status         = $dbStatus0; // valid|invalid|pending|rejected
        $movement->confirm_status = 'pending';  // siempre vuelve a pending

        // Auditoría 1ª validación
        $movement->aprobacion_1   = now();
        $movement->aprobado_por_1 = Auth::id();
        $movement->save();


        // Enviar email si quedó "observed" (invalid)
        if ($validated['status0'] === 'observed') {
            try {
                // requiere método helper en el modelo Deposit:
                // public function sendDepositObservedEmail(?string $message = null) { ... }
                $deposit->sendDepositObservedEmail($validated['notify_message'] ?? null);
            } catch (\Throwable $e) {
                // opcional: loggear
                // \Log::warning('No se pudo enviar email de depósito observado (1ª val.): '.$e->getMessage());
            }

            $deposit->status_conclusion = 'pending';
        } elseif ($validated['status0'] === 'rejected') {
            try {
                $deposit->sendDepositRejectedEmail();
            } catch (\Throwable $e) {
                // opcional: loggear
                // \Log::warning('No se pudo enviar email de depósito observado (2ª val.): '.$e->getMessage());
            }
            $deposit->status_conclusion = 'rejected';
        }


         $deposit->save();

        return response()->json([
            'message' => 'Primera validación actualizada correctamente.',
            'data'    => new DepositResource($deposit),
        ]);
    }


    public function updateStatus(string $id, Request $request)
    {
        $validated = $request->validate([
            // UI status for 2ª validación
            'status'     => 'required|in:approved,observed,rejected,pending',
            // Comentarios aceptando cualquiera de los dos nombres
            'comment'    => 'nullable|string|max:1000',
            'conclusion' => 'nullable|string|max:1000',
            'notify_message' => 'nullable|string|max:1000',
        ]);

        $deposit = Deposit::with('movement')->findOrFail($id);
        Gate::authorize('update', $deposit);

        $movement = $deposit->movement;
        if (!$movement) {
            return response()->json([
                'message' => 'No se encontró el movimiento asociado al depósito.'
            ], 404);
        }

        // Solo exigimos 1ª validación "valid" si se intenta APROBAR en 2ª
        if ($validated['status'] === 'approved' && $movement->status->value !== 'valid') {
            return response()->json([
                'message' => 'Debe completar y aprobar la primera validación antes de aprobar la segunda.'
            ], 422);
        }

        // UI → DB para confirm_status (2ª validación)
        // approved|observed|pending|rejected → confirmed|invalid|pending|rejected
        $uiToDbConfirm = [
            'approved' => 'confirmed',
            'observed' => 'invalid',
            'pending'  => 'pending',
            'rejected' => 'rejected',
        ];
        $dbConfirmStatus = $uiToDbConfirm[$validated['status']];

        // Guardar comentario independiente (acepta 'conclusion' o 'comment')
        $note = $validated['comment'] ?? $validated['conclusion'] ?? null;
        if (!empty($note)) {
            $deposit->comment = $note;
        }
        $deposit->updated_by = Auth::id();
        $deposit->save();

        // Actualizar 2ª validación
        $movement->confirm_status = $dbConfirmStatus;

        // 🔁 Cruzar el reset:
        // Si la 2ª validación queda "observed" (invalid) o "rejected",
        // forzamos la 1ª validación a "pending" (status0 ← pending).
        if (in_array($validated['status'], ['observed', 'rejected'], true)) {
            $movement->status = 'pending'; // (o MovementStatus::PENDING si prefieres enum)
        }

        // Auditoría 2ª validación
        $movement->aprobacion_2   = now();
        $movement->aprobado_por_2 = Auth::id();
        $movement->save();

        if ($validated['status'] === 'observed') {
            try {
                $deposit->sendDepositObservedEmail($validated['notify_message'] ?? null);
            } catch (\Throwable $e) {
                // opcional: loggear
                // \Log::warning('No se pudo enviar email de depósito observado (2ª val.): '.$e->getMessage());
            }

            $deposit->status_conclusion = 'pending';
        } elseif ($validated['status'] === 'approved') {
            try {
                $deposit->sendDepositApprovedEmail();
            } catch (\Throwable $e) {
                // opcional: loggear
                // \Log::warning('No se pudo enviar email de depósito observado (2ª val.): '.$e->getMessage());
            }
            $deposit->status_conclusion = 'approved';
        } elseif ($validated['status'] === 'rejected') {
            try {
                $deposit->sendDepositRejectedEmail();
            } catch (\Throwable $e) {
                // opcional: loggear
                // \Log::warning('No se pudo enviar email de depósito observado (2ª val.): '.$e->getMessage());
            }

            $deposit->status_conclusion = 'rejected';
        }

        $deposit->save();


        if ($validated['status'] === 'approved') {
            $investor = Investor::findOrFail($movement->investor_id);
            $balance = $investor->getBalance($movement->currency);
            $balanceAmountMoney = MoneyConverter::fromDecimal($balance->amount, $balance->currency);
            $movementAmountMoney = MoneyConverter::fromDecimal($movement->amount, $movement->currency);
            $balance->amount = $balanceAmountMoney->add($movementAmountMoney);
            $balance->save();
        }

        return response()->json([
            'message' => 'Segunda validación actualizada correctamente.',
            'data'    => new DepositResource($deposit),
        ]);
    }




    public function show($id)
    {
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
    public function validateDeposit($movementId)
    {
        $movement = Movement::findOrFail($movementId);

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
    public function rejectDeposit(Request $request, $depositId, $movementId)
    {
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
            'estadoConfig' => 'rejected',
            'conclusion' => $request->input('conclusion') // 👈 opcional (puede ser null)
        ]);

        return response()->json(['message' => 'Depósito rechazado correctamente']);
    }
    public function approveDeposit(Request $request, $depositId, $movementId)
    {
        try {
            DB::beginTransaction();
            $movement = Movement::findOrFail($movementId);
            if ($movement->status !== MovementStatus::VALID || $movement->confirm_status === MovementStatus::VALID) {
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
                'approval2_by' => Auth::id(),
                'estadoConfig' => 'confirmed',
                'conclusion' => $request->input('conclusion')
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
    public function rejectConfirmDeposit(Request $request, $depositId, $movementId)
    {
        $movement = Movement::findOrFail($movementId);

        if (
            $movement->status !== MovementStatus::VALID ||
            $movement->confirm_status !== MovementStatus::PENDING
        ) {
            return response()->json(['message' => 'El movimiento no es válido para rechazar'], 400);
        }

        $movement->confirm_status = MovementStatus::REJECTED;
        $movement->save();

        $deposit = Deposit::findOrFail($depositId);
        $deposit->update([
            'estadoConfig' => 'rejected',
            'conclusion' => $request->input('conclusion') // 👈 opcional
        ]);

        return response()->json(['message' => 'Depósito rechazado en confirmación']);
    }
    public function exportExcel(Request $request)
    {
        try {
            // Autorización (descomentala si la necesitas)
            // Gate::authorize('export', Deposit::class);
            $search = $request->input('search', '');
            $status = $request->input('status', null);
            $query = Deposit::query()->with([
                'investor.user',
                'bankAccount.bank',
                'movement.aprobadoPor1',
                'movement.aprobadoPor2'
            ]);
            if ($status) {
                $query->whereHas('movement', function ($q) use ($status) {
                    $q->where('status', $status);
                });
            }
            if ($search) {
                $query->whereHas('investor.user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('apellidos', 'like', "%{$search}%");
                });
            }
            $primarySort = Schema::hasColumn('deposits', 'deposit_date') ? 'deposit_date'
                : (Schema::hasColumn('deposits', 'date') ? 'date'
                    : (Schema::hasColumn('deposits', 'created_at') ? 'created_at' : 'id'));
            $deposits = $query->orderByDesc($primarySort)
                ->orderByDesc('id')
                ->get();
            $currentDateTime = Carbon::now()->format('d-m-Y_H-i-s');
            $fileName = "depositos_{$currentDateTime}.xlsx";
            return Excel::download(new DepositsExport($deposits), $fileName);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'No tienes permiso para exportar los depósitos.'
            ], 403);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al exportar los depósitos.',
                'error'   => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ], 500);
        }
    }



    public function uploadAttachments(string $id, Request $request)
    {
        $deposit = Deposit::findOrFail($id);
        Gate::authorize('update', $deposit);

        $request->validate([
            'files'   => 'required|array',
            'files.*' => 'file|max:20480', // 20MB per file; adjust as needed
        ]);

        $stored = [];

        foreach ($request->file('files', []) as $file) {
            $path = $file->store("deposits/{$deposit->id}", config('filesystems.default'));

            $attachment = $deposit->attachments()->create([
                'path'        => $path,
                'name'        => $file->getClientOriginalName(),
                'mime'        => $file->getClientMimeType(),
                'size'        => $file->getSize(),
                'uploaded_by' => Auth::id(),
            ]);

            $stored[] = $attachment->fresh();
        }

        return response()->json([
            'message'     => 'Archivos adjuntados correctamente.',
            'attachments' => collect($stored)->sortBy('created_at')->values()->map(fn($a) => [

                'id'   => $a->id,
                'name' => $a->name,
                'mime' => $a->mime,
                'size' => $a->size,
                'url'  => $a->url,
                'is_image' => $a->is_image,
                'ext' => $a->ext,
                'created_at' => optional($a->created_at)->toISOString(),
            ]),
        ], 201);
    }



    public function listAttachments(string $id)
    {
        $deposit = Deposit::findOrFail($id);
        Gate::authorize('view', $deposit);

        return response()->json([
            'attachments' => $deposit->attachments()->orderBy('created_at', 'asc')->get()->map(fn($a) => [
                'id'   => $a->id,
                'name' => $a->name,
                'mime' => $a->mime,
                'size' => $a->size,
                'url'  => $a->url,
                'is_image' => $a->is_image,
                'ext'  => $a->ext,
                'created_at' => optional($a->created_at)->toISOString(),

            ]),
        ]);
    }


    public function deleteAttachment(string $id, string $attachmentId)
    {
        $deposit = Deposit::findOrFail($id);
        Gate::authorize('update', $deposit);

        $attachment = DepositAttachment::where('deposit_id', $id)->findOrFail($attachmentId);

        // Attempt to delete file from storage; ignore failure
        if ($attachment->path && Storage::disk(config('filesystems.default'))->exists($attachment->path)) {
            Storage::disk(config('filesystems.default'))->delete($attachment->path);
        }

        $attachment->delete();

        return response()->json(['message' => 'Adjunto eliminado correctamente.']);
    }
    
}
