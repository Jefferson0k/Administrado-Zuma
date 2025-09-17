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
        return response()->json($bankAccount);
    }
    public function showBank($id)
    {
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

    public function validateBankAccount($id)
    {
        try {
            DB::beginTransaction();
            $bankAccount = BankAccount::findOrFail($id);
            if ($bankAccount->status === 'valid') {
                return response()->json(['message' => 'La cuenta ya está validada'], 400);
            }
            $bankAccount->status = 'valid';
            $bankAccount->save();
            $bankAccount->sendBankAccountValidationEmail();
            DB::commit();
            return response()->json([
                'message' => 'La cuenta bancaria ha sido validada correctamente.'
            ]);
        } catch (Throwable $th) {
            DB::rollBack();
            Log::error('Error al validar cuenta bancaria: ' . $th->getMessage(), [
                'id' => $id,
                'trace' => $th->getTraceAsString(),
            ]);
            if (config('app.debug')) {
                return response()->json([
                    'message' => 'Error al validar la cuenta bancaria.',
                    'error' => $th->getMessage(),
                    'trace' => $th->getTrace()
                ], 500);
            }
            return response()->json([
                'message' => 'Error al validar la cuenta bancaria.'
            ], 500);
        }
    }
    public function rejectBankAccount($id)
    {
        try {
            DB::beginTransaction();
            $bankAccount = BankAccount::findOrFail($id);
            if ($bankAccount->status === 'rejected') {
                return response()->json(['message' => 'La cuenta ya está rechazada'], 400);
            }
            $bankAccount->status = 'rejected';
            $bankAccount->save();
            $bankAccount->sendBankAccountRejectionEmail();
            DB::commit();
            return response()->json([
                'message' => 'La cuenta bancaria ha sido rechazada y se ha enviado un correo al inversionista.'
            ]);
        } catch (Throwable $th) {
            DB::rollBack();
            Log::error('Error al rechazar cuenta bancaria: ' . $th->getMessage(), [
                'id' => $id,
                'trace' => $th->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Error al rechazar la cuenta bancaria.'
            ], 500);
        }
    }


    public function storeAttachments(string $id, Request $request)
    {
        $account = BankAccount::findOrFail($id);
        // Gate::authorize('update', $account); // si usas Policy

        $request->validate([
            'files'   => ['required', 'array', 'min:1'], // <- OBLIGATORIO: min:1 (no min[1])
            'files.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,heic', 'max:10240'], // 10 MB (en KB)
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
                'url'          => Storage::disk('s3')->temporaryUrl($attachment->path, now()->addMinutes(60)),
                'download_url' => Storage::disk('s3')->temporaryUrl($attachment->path, now()->addMinutes(60)),
                'mime_type'     => $attachment->mime_type,
                'size'          => $attachment->size,
            ];
        }

        return response()->json(['files' => $stored], 201);
    }




    // App\Http\Controllers\Panel\BankAccountController.php

    public function updateStatus0(string $id, Request $request)
    {
        $account = BankAccount::findOrFail($id);
        Gate::authorize('update', $account); // opcional si usas Policies

        $validated = $request->validate([
            'status0' => 'required|in:approved,observed,rejected',
            'comment0' => 'nullable|string|max:1000',
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

        $account->status0 = $validated['status0']; // approved|observed|rejected
        $account->status = 'pending'; // approved|observed|rejected
        $account->comment0 = $validated['comment0'] ?? null;

        $account->updated0_by = Auth::id();
        $account->updated0_at = now();
        $account->save();

        return new BankAccountResource($account);
    }

    public function updateStatus(string $id, Request $request)
    {
        $account = BankAccount::findOrFail($id);
        Gate::authorize('update', $account); // opcional

        if ($account->status0 !== 'approved') {
            return response()->json([
                'message' => 'La primera validación (status0) debe estar aprobada antes de actualizar el estado.'
            ], 422);
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,observed,rejected',
            'comment' => 'nullable|string|max:1000',
        ]);

        $account->status = $validated['status'];
        $account->comment = $validated['comment'] ?? null;

        // 🔁 Nuevo: si la 2da validación NO es "approved", regresamos status0 a "pending"
        if (in_array($account->status, ['observed', 'rejected'], true)) {
            $account->status0 = 'pending';
        }

        $account->updated_by = Auth::id();
        $account->updated_last_at = now();

        $account->save();

        // Notificaciones (si ya las tienes)
        // if ($account->status === 'approved') {
        //     try {
        //         $account->sendBankAccountValidationEmail();
        //     } catch (\Throwable $e) {
        //     }
        // } elseif ($account->status === 'rejected') {
        //     try {
        //         $account->sendBankAccountRejectionEmail();
        //     } catch (\Throwable $e) {
        //     }
        // }

        return new BankAccountResource($account);
    }


    public function indexAttachments(string $id)
    {
        $account = BankAccount::with('attachments')->findOrFail($id);
        // $this->authorize('view', $account); // si usas policies

        $files = $account->attachments()
            ->latest()
            ->get()
            ->map(fn($a) => [
                'id'            => $a->id,
                'original_name' => $a->original_name,
                'url'          => Storage::disk('s3')->temporaryUrl($a->path, now()->addMinutes(60)), // URL presignada (preview)
                'download_url' => Storage::disk('s3')->temporaryUrl($a->path, now()->addMinutes(60)), // URL presignada (descarga)
                'mime_type'     => $a->mime_type,
                'size'          => $a->size,
            ]);






        return response()->json(['files' => $files], 200);
    }
}
