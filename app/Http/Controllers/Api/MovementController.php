<?php

namespace App\Http\Controllers\Api;

use App\Enums\MovementStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Tasas\Movement\MovementResource;
use App\Models\Movement;
use App\Models\FixedTermInvestment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MovementController extends Controller{
    public function listTasasFijas(Request $request){
        $movements = Movement::with('deposit')
            ->where('description', 'tasas_fijas')
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 10));

        return MovementResource::collection($movements)
            ->additional(['success' => true]);
    }
    public function listHipotecas(Request $request){
        $movements = Movement::with('deposit')
            ->where('description', 'hipotecas')
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 10));

        return MovementResource::collection($movements)
            ->additional(['success' => true]);
    }
    public function aceptarTasasFijas(string $id){
        DB::transaction(function () use ($id) {
            $movement = Movement::with('deposit')->findOrFail($id);
            $movement->update([
                'status' => MovementStatus::VALID,
                'confirm_status' => MovementStatus::CONFIRMED,
            ]);
            $deposit = $movement->deposit;
            if ($deposit && $deposit->fixed_term_investment_id) {
                $deposit->fixedTermInvestment()->update([
                    'status' => 'activo',
                ]);
            }
        });
        return response()->json([
            'success' => true,
            'message' => 'Movimiento aceptado y estado de inversión activado.',
        ]);
    }
    public function rechazarTasasFijas(string $id){
        DB::transaction(function () use ($id) {
            $movement = Movement::with('deposit')->findOrFail($id);
            $movement->update([
                'status' => MovementStatus::INVALID,
                'confirm_status' => MovementStatus::REJECTED,
            ]);
        });
        return response()->json([
            'success' => true,
            'message' => 'Movimiento rechazado correctamente.',
        ]);
    }
}
