<?php

namespace App\Http\Controllers\Panel;
use App\Http\Controllers\Controller;
use App\Http\Resources\Factoring\BankAccount\BankAccountResource;
use App\Models\BankAccount;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Throwable;

class BankAccountsController extends Controller {
    public function index(){
        try {
            Gate::authorize('viewAny', BankAccount::class);
            $sectors = BankAccount::all();
            return response()->json([
                'total' => $sectors->count(),
                'data'  => BankAccountResource::collection($sectors),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'No tienes permiso para ver las cuentas bancarias.'
            ], 403);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al listar las cuentas bancarias.'
            ], 500);
        }
    }
}
