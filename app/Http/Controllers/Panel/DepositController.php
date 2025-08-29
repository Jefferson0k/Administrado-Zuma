<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\Factoring\Deposit\DepositResource;
use App\Models\Deposit;
use Illuminate\Auth\Access\AuthorizationException;
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
                'message' => 'No tienes permiso para ver los sectores.'
            ], 403);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al listar los sectores.'
            ], 500);
        }
    }
}
