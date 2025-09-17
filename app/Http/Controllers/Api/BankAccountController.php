<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BankAccount\StoreBankAccountRequest;
use App\Http\Requests\BankAccount\UpdateBankAccountRequest;
use App\Models\BankAccount;
use App\Models\Balance;
use App\Models\Investor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Alias;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Cuentas Bancarias",
 *     description="Endpoints para gestionar cuentas bancarias de inversores"
 * )
 */
class BankAccountController extends Controller{
    public function index(Request $request)
    {
        try {
            /** @var \App\Models\Investor $investor */
            $investor = Auth::user();

            $bank_accounts = $investor->bankAccounts()
                ->selectRaw('bank_accounts.*, banks.name as bank')
                ->join('banks', 'bank_accounts.bank_id', '=', 'banks.id')
                ->lazy();

            return response()->json([
                'success' => true,
                'data' => $bank_accounts,
            ]);
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
    public function store(StoreBankAccountRequest $request){
        try {
            $validatedData = $request->validated();

            /** @var \App\Models\User $investor */
            $investor = Auth::user();

            // 1️⃣ Validar alias prohibido
            $aliasProhibido = Alias::where('slug', Str::slug($validatedData['alias']))->exists();
            if ($aliasProhibido) {
                return response()->json([
                    'success' => false,
                    'message' => 'El alias ingresado no está permitido, por favor elige otro.',
                ], 422);
            }

            // 2️⃣ Validar que no exista ya en las cuentas del inversor
            $aliasExistente = BankAccount::where('alias', $validatedData['alias'])
                ->where('investor_id', $investor->id)
                ->exists();
            if ($aliasExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya tienes una cuenta bancaria registrada con ese alias.',
                ], 422);
            }

            // Crear nueva cuenta bancaria
            $bank_account = new BankAccount();
            $bank_account->bank_id = $request->bank_id; // ✅
            $bank_account->type = $request->type;
            $bank_account->currency = $request->currency;
            $bank_account->cc = $request->cc;
            $bank_account->cci = $request->cci;
            $bank_account->alias = $request->alias;
            $bank_account->status = 'pending';
            $bank_account->investor_id = $investor->id;

            // Verificar si existe balance para esa moneda
            $balances = Investor::find($bank_account->investor_id)
                ->balances()
                ->where('currency', $bank_account->currency)
                ->first();

            if (is_null($balances)) {
                $balance = new Balance();
                $balance->currency = $bank_account->currency;
                $balance->amount = 0;
                $balance->investor_id = $bank_account->investor_id;
                $balance->save();
            }

            $bank_account->save();

            return response()->json([
                'success' => true,
                'message' => 'Operación realizada correctamente.',
                'data' => null,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    public function update(UpdateBankAccountRequest $request, string $bankAccountID)
    {
        try {
            $validatedData = $request->validated();

            $bank_account = BankAccount::find($bankAccountID);
            if (!$bank_account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recurso no encontrado.',
                ], 404);
            }
            $bank_account->update([
                'alias' => $validatedData['alias'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Operación realizada correctamente.',
                'data' => null,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }
    public function destroy(string $bankAccountID){
        try {
            $bank_account = BankAccount::find($bankAccountID);
            if (!$bank_account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recurso no encontrado.',
                ], 404);
            }

            $bank_account->delete();

            return response()->json([
                'success' => true,
                'message' => 'Operación realizada correctamente.',
                'data' => null,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }
}
