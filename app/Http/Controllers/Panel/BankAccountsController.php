<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\Factoring\BankAccount\BankAccountResource;
use App\Models\BankAccount;
use App\Models\Deposit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

class BankAccountsController extends Controller
{
    public function index()
    {
        try {
            Gate::authorize('viewAny', BankAccount::class);

            $accounts = BankAccount::latest()->paginate(10);

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
}
