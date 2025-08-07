<?php

namespace App\Http\Controllers\Panel;

use App\Models\VisitaProducto;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
class VisitaProductoController extends Controller{
    public function registrar($producto_id, Request $request){
        $ip = $request->ip();
        $yaVisitado = VisitaProducto::where('ip', $ip)
                        ->where('producto_id', $producto_id)
                        ->exists();
        if (!$yaVisitado) {
            VisitaProducto::create([
                'ip' => $ip,
                'producto_id' => $producto_id,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Visita registrada',
                'producto_id' => $producto_id,
                'ip' => $ip
            ], 201);
        }
        return response()->json([
            'success' => false,
            'message' => 'Esta IP ya ha registrado una visita a este producto',
            'producto_id' => $producto_id,
            'ip' => $ip
        ], 200);
    }
    public function visitasPorProducto(): JsonResponse {
        $visitas = VisitaProducto::select('producto_id')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('producto_id')
            ->get();
        return response()->json([
            'success' => true,
            'data' => $visitas
        ]);
    }
}
