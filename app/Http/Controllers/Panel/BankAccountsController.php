<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\Factoring\BankAccount\BankAccountResource;
use App\Models\BankAccount;
use App\Models\BankAccountAttachment;
use App\Models\Deposit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\HistoryAprobadorBankAccount;

class BankAccountsController extends Controller
{
    public function index(Request $request)
    {
        try {
            Gate::authorize('viewAny', BankAccount::class);

            $search  = trim((string) $request->input('search', ''));
            $perPage = (int) $request->input('perPage', 10);

            $accounts = BankAccount::query()
                ->when($search !== '', function ($q) use ($search) {
                    $q->whereHas('investor', function ($iq) use ($search) {
                        $iq->where('name', 'like', '%' . $search . '%');
                    });
                })
                ->latest()
                ->paginate($perPage)
                ->appends(['search' => $search, 'perPage' => $perPage]);

            // Devuelve data + meta/links automáticamente
            return BankAccountResource::collection($accounts);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para ver las cuentas bancarias.'], 403);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al listar las cuentas bancarias.'], 500);
        }
    }


    public function show($id)
    {

        $bankAccount = BankAccount::with('bank', 'investor')->findOrFail($id);
        Gate::authorize('view', $bankAccount);
        return response()->json($bankAccount);
    }
    public function showBank($id)
    {
        Gate::authorize('viewAny', BankAccount::class);
        $deposits = Deposit::with(['movement', 'investor'])
            ->where('bank_account_id', $id)
            ->get();
        return Inertia::render('panel/Factoring/BankAccounts/Desarrollo/showBankAccounsts', [
            'bank_account_id' => $id,
            'total' => $deposits->count(),
            'deposits' => $deposits->map(function ($deposit) {
                return [
                    'deposit_id'    => $deposit->id,
                    'nro_operation' => $deposit->nro_operation,
                    'currency'      => $deposit->currency,
                    'amount'        => $deposit->amount,
                    'resource'      => $deposit->resource_path,
                    'investor'      => [
                        'id'   => $deposit->investor?->id,
                        'name' => $deposit->investor?->name,
                    ],
                    'movement'      => [
                        'id'        => $deposit->movement?->id,
                        'type'      => $deposit->movement?->type,
                        'status'    => $deposit->movement?->status,
                        'confirm'   => $deposit->movement?->confirm_status,
                        'amount'    => $deposit->movement?->amount,
                        'formatted' => $deposit->movement?->amount_formatted,
                    ],
                ];
            }),
        ]);
    }

    // public function validateBankAccount($id)
    // {
    //     try {
    //         DB::beginTransaction();
    //         $bankAccount = BankAccount::findOrFail($id);
    //         if ($bankAccount->status === 'valid') {
    //             return response()->json(['message' => 'La cuenta ya está validada'], 400);
    //         }
    //         $bankAccount->status = 'valid';
    //         $bankAccount->save();
    //         $bankAccount->sendBankAccountValidationEmail();
    //         DB::commit();
    //         return response()->json([
    //             'message' => 'La cuenta bancaria ha sido validada correctamente.'
    //         ]);
    //     } catch (Throwable $th) {
    //         DB::rollBack();
    //         Log::error('Error al validar cuenta bancaria: ' . $th->getMessage(), [
    //             'id' => $id,
    //             'trace' => $th->getTraceAsString(),
    //         ]);
    //         if (config('app.debug')) {
    //             return response()->json([
    //                 'message' => 'Error al validar la cuenta bancaria.',
    //                 'error' => $th->getMessage(),
    //                 'trace' => $th->getTrace()
    //             ], 500);
    //         }
    //         return response()->json([
    //             'message' => 'Error al validar la cuenta bancaria.'
    //         ], 500);
    //     }
    // }
    // public function rejectBankAccount($id)
    // {
    //     try {
    //         DB::beginTransaction();
    //         $bankAccount = BankAccount::findOrFail($id);
    //         if ($bankAccount->status === 'rejected') {
    //             return response()->json(['message' => 'La cuenta ya está rechazada'], 400);
    //         }
    //         $bankAccount->status = 'rejected';
    //         $bankAccount->save();
    //         $bankAccount->sendBankAccountRejectionEmail();
    //         DB::commit();
    //         return response()->json([
    //             'message' => 'La cuenta bancaria ha sido rechazada y se ha enviado un correo al inversionista.'
    //         ]);
    //     } catch (Throwable $th) {
    //         DB::rollBack();
    //         Log::error('Error al rechazar cuenta bancaria: ' . $th->getMessage(), [
    //             'id' => $id,
    //             'trace' => $th->getTraceAsString(),
    //         ]);
    //         return response()->json([
    //             'message' => 'Error al rechazar la cuenta bancaria.'
    //         ], 500);
    //     }
    // }


    public function storeAttachments(string $id, Request $request)
    {
        try {
            $account = BankAccount::findOrFail($id);
            // Gate::authorize('update', $account); // si usas Policy
            Gate::authorize('uploadFiles', $account); // si usas Policy

            $request->validate([
                'files'   => ['required', 'array', 'min:1'], // <- OBLIGATORIO: min:1 (no min[1])
                'files.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,heic'], // 10 MB (en KB)
            ]);

            $stored = [];

            foreach ($request->file('files', []) as $file) {
                $path = $file->store("bank_accounts/{$account->id}", 's3');

                $attachment = BankAccountAttachment::create([
                    'bank_account_id' => $account->id,
                    'original_name'   => $file->getClientOriginalName(),
                    'path'            => $path,
                    'mime_type'       => $file->getClientMimeType(),
                    'size'            => $file->getSize(),
                    'uploaded_by'     => auth()->id(), // null si no hay auth()
                    'meta'            => [
                        'uuid' => (string) Str::uuid(),
                    ],
                ]);

                $stored[] = [
                    'id'            => $attachment->id,
                    'original_name' => $attachment->original_name,
                    'url'           => url('/s3/' . $attachment->path),      // 👈 uses your proxy route
                    'download_url'  => url('/s3/' . $attachment->path),      // 👈 same
                    'mime_type'     => $attachment->mime_type,
                    'size'          => $attachment->size,
                ];
            }

            return response()->json(['files' => $stored], 201);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para subir archivos a esta cuenta bancaria.'], 403);
        } catch (Throwable $e) {
            Log::error('Error al subir archivos a cuenta bancaria: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Error al subir archivos a la cuenta bancaria.'], 500);
        }
    }

    public function destroyAttachment(string $id, string $attachmentId)
    {
        try {
            $account = BankAccount::findOrFail($id);
            // Usa la misma policy que para subir (ajústala si tienes otra para borrar)
            Gate::authorize('deleteFiles', $account);

            $attachment = BankAccountAttachment::where('bank_account_id', $account->id)
                ->where('id', $attachmentId)
                ->firstOrFail();

            // Borra el archivo del S3 (ignora si no existe)
            try {
                Storage::disk('s3')->delete($attachment->path);
            } catch (\Throwable $e) {
                // continúa igual si el delete del archivo falla; igual se borra el registro
            }

            $attachment->delete();

            return response()->json([
                'message' => 'Archivo eliminado correctamente.',
                'id'      => (int) $attachmentId,
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para eliminar archivos de esta cuenta bancaria.'], 403);
        } catch (\Throwable $e) {
            Log::error('Error al eliminar archivo de cuenta bancaria: ' . $e->getMessage(), [
                'id' => $id,
                'attachmentId' => $attachmentId,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Error al eliminar el archivo adjunto.'], 500);
        }
    }



    public function updateStatus0(string $id, Request $request)
    {
        try {
            $account = BankAccount::findOrFail($id);
            Gate::authorize('approve1', $account); // opcional si usas Policies

            $validated = $request->validate([
                'status0' => 'required|in:approved,observed,rejected',
                'comment0' => 'required|string|max:1000',
                'notify_message'  => 'nullable|string|max:1000',
            ]);

            // ❗ Si intentan aprobar (approved), exigir al menos 1 adjunto
            if ($validated['status0'] === 'approved') {
                // Asegúrate de tener la relación attachments() en el modelo

                if (!$account->attachments()->exists()) {
                    return response()->json([
                        'message' => 'Debes adjuntar y subir al menos un archivo antes de aprobar la primera validación.'
                    ], 422);
                }
            }


            if ($validated['status0'] === 'rejected') {
                $account->status_conclusion = 'rejected';
                try {
                    $account->sendBankAccountRejectionEmail();
                } catch (\Throwable $e) {
                }
            } else {
                $account->status_conclusion = 'pending';
            }


            try {
                $messageForClient = $validated['notify_message'] ?? null;

                if ($messageForClient) {
                    // Envía correo distinto según opción seleccionada
                    if (str_contains($messageForClient, 'Entidad bancaria errónea')) {
                        Log::info('Enviando correo de observación: entidad bancaria errónea.');
                        $account->sendBankAccountObservedWrongBankEmail($messageForClient);
                    } elseif (str_contains($messageForClient, 'Error en tipo de cuenta bancaria')) {
                        Log::info('Enviando correo de observación: error en tipo de cuenta.');
                        $account->sendBankAccountObservedAccountTypeErrorEmail($messageForClient);
                    } elseif (str_contains($messageForClient, 'Número de cuenta bancaria erróneo')) {
                        Log::info('Enviando correo de observación: número de cuenta erróneo.');
                        $account->sendBankAccountObservedAccountNumberErrorEmail($messageForClient);
                    } elseif (str_contains($messageForClient, 'Cuenta mancomunada')) {
                        Log::info('Enviando correo de observación: cuenta mancomunada.');
                        $account->sendBankAccountObservedJointAccountEmail($messageForClient);
                    } elseif (str_contains($messageForClient, 'Cuentas intangibles')) {
                        Log::info('Enviando correo de observación: cuenta intangible (AFP/ONP/CTS, etc.).');
                        $account->sendBankAccountObservedIntangibleAccountEmail($messageForClient);
                    } else {
                        // genérico (fallback)
                        Log::info('Enviando correo de observación genérico.');
                        $account->sendBankAccountObservedEmail($messageForClient);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Error enviando correo de observación: ' . $e->getMessage());
            }

            $account->status0 = $validated['status0']; // approved|observed|rejected
            $account->status = 'pending'; // approved|observed|rejected
            $account->comment0 = $validated['comment0'] ?? null;

            $account->updated0_by = Auth::id();
            $account->updated0_at = now();
            $account->save();


            // Guardar en historial
            HistoryAprobadorBankAccount::create([
                'bank_account_id'   => $account->id,
                'approval1_status'  => $account->status0,
                'approval1_by'      => Auth::id(),
                'approval1_comment' => $account->comment0,
                'approval1_at'      => $account->updated0_at,
            ]);



            return new BankAccountResource($account);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para actualizar la primera validación de esta cuenta bancaria.'], 403);
        } catch (\Throwable $e) {
            Log::error('Error al actualizar la primera validación de cuenta bancaria: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Error al actualizar la primera validación de la cuenta bancaria.'], 500);
        }
    }

    public function updateStatus(string $id, Request $request)
    {
        try {
            $account = BankAccount::findOrFail($id);
            Gate::authorize('approve2', $account); // opcional

            if ($account->status0 !== 'approved') {
                return response()->json([
                    'message' => 'La primera validación (status0) debe estar aprobada antes de actualizar el estado.'
                ], 422);
            }

            $validated = $request->validate([
                'status' => 'required|in:approved,observed,rejected',
                'comment' => 'required|string|max:1000',
                'notify_message'  => 'nullable|string|max:1000',
            ]);

            $account->status = $validated['status'];
            $account->comment = $validated['comment'] ?? null;

            // 🔁 Nuevo: si la 2da validación NO es "approved", regresamos status0 a "pending"
            if (in_array($account->status, ['observed', 'rejected'], true)) {
                $account->status0 = 'pending';
            }

            if (in_array($account->status, ['rejected'], true)) {
                $account->status_conclusion = 'rejected';
            } else if (in_array($account->status, ['approved'], true)) {
                $account->status_conclusion = 'approved';
            } else {
                $account->status_conclusion = 'pending';
            }



            $account->updated_by = Auth::id();
            $account->updated_last_at = now();

            $account->save();


            $history  = HistoryAprobadorBankAccount::where('bank_account_id', $account->id)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            // Guardar en historial
            $history?->update([
                'bank_account_id'   => $account->id,
                'approval2_status'  => $account->status,
                'approval2_by'      => Auth::id(),
                'approval2_comment' => $account->comment,
                'approval2_at'      => $account->updated0_at,
            ]);

            //Notificaciones (si ya las tienes)
            if ($account->status === 'approved') {
                try {
                    $account->sendBankAccountValidationEmail();
                } catch (\Throwable $e) {
                }
            } elseif ($account->status === 'rejected') {
                try {
                    $account->sendBankAccountRejectionEmail();
                } catch (\Throwable $e) {
                    Log::warning('Error enviando correo de rechazo: ' . $e->getMessage());
                }
            } elseif ($account->status === 'observed') {
                try {
                    // usa SOLO el mensaje del popup (notify_message), no el comment
                    $messageForClient = $validated['notify_message'] ?? null;

                    if ($messageForClient) {
                        // Envía correo distinto según opción seleccionada
                        if (str_contains($messageForClient, 'Entidad bancaria errónea')) {
                            Log::info('Enviando correo de observación: entidad bancaria errónea.');
                            $account->sendBankAccountObservedWrongBankEmail($messageForClient);
                        } elseif (str_contains($messageForClient, 'Error en tipo de cuenta bancaria')) {
                            Log::info('Enviando correo de observación: error en tipo de cuenta.');
                            $account->sendBankAccountObservedAccountTypeErrorEmail($messageForClient);
                        } elseif (str_contains($messageForClient, 'Número de cuenta bancaria erróneo')) {
                            Log::info('Enviando correo de observación: número de cuenta erróneo.');
                            $account->sendBankAccountObservedAccountNumberErrorEmail($messageForClient);
                        } elseif (str_contains($messageForClient, 'Cuenta mancomunada')) {
                            Log::info('Enviando correo de observación: cuenta mancomunada.');
                            $account->sendBankAccountObservedJointAccountEmail($messageForClient);
                        } elseif (str_contains($messageForClient, 'Cuentas intangibles')) {
                            Log::info('Enviando correo de observación: cuenta intangible (AFP/ONP/CTS, etc.).');
                            $account->sendBankAccountObservedIntangibleAccountEmail($messageForClient);
                        } else {
                            // genérico (fallback)
                            Log::info('Enviando correo de observación genérico.');
                            $account->sendBankAccountObservedEmail($messageForClient);
                        }
                    }
                } catch (\Throwable $e) {
                }
            }

            return new BankAccountResource($account);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para actualizar la segunda validación de esta cuenta bancaria.'], 403);
        } catch (\Throwable $e) {
            Log::error('Error al actualizar la segunda validación de cuenta bancaria: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Error al actualizar la segunda validación de la cuenta bancaria.'], 500);
        }
    }


    public function indexAttachments(string $id)
    {
        $account = BankAccount::with('attachments')->findOrFail($id);
        // $this->authorize('view', $account); // si usas policies
        Gate::authorize('view', $account); // si usas Policy

        $files = $account->attachments()
            ->latest()
            ->get()
            ->map(fn($a) => [
                'id'            => $a->id,
                'original_name' => $a->original_name,
                'url'           => url('/s3/' . $a->path),      // 👈 proxy URL (resolvable)
                'download_url'  => url('/s3/' . $a->path),      // 👈 proxy URL (resolvable)
                'mime_type'     => $a->mime_type,
                'size'          => $a->size,
            ]);






        return response()->json(['files' => $files], 200);
    }


    public function history(BankAccount $bankAccount)
    {
        $rows = HistoryAprobadorBankAccount::with([
            'approval1By:id,name',
            'approval2By:id,name',
        ])
            ->where('bank_account_id', $bankAccount->id)
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($h) {
                return [
                    'id'                   => $h->id,

                    'approval1_status'     => $h->approval1_status,
                    'approval1_by_name'    => optional($h->approval1By)->name,
                    'approval1_at'         => $h->approval1_at,
                    'approval1_comment'    => $h->approval1_comment,

                    'approval2_status'     => $h->approval2_status,
                    'approval2_by_name'    => optional($h->approval2By)->name,
                    'approval2_at'         => $h->approval2_at,
                    'approval2_comment'    => $h->approval2_comment,
                ];
            });

        return response()->json(['data' => $rows]);
    }
}
