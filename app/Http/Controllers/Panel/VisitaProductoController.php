<?php

namespace App\Http\Controllers\Panel;

use App\Models\VisitaProducto;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VisitaProductoController extends Controller{
    public function registrar($id, Request $request){
        try {
            $ip = $request->ip();
            $yaVisitado = VisitaProducto::where('ip', $ip)
                            ->where('producto_id', $id)
                            ->exists();
            if (!$yaVisitado) {
                VisitaProducto::create([
                    'ip' => $ip,
                    'producto_id' => $id,
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Visita registrada',
                    'producto_id' => $id,
                    'ip' => $ip
                ], 201);
            }
            return response()->json([
                'success' => false,
                'message' => 'Esta IP ya ha registrado una visita a este producto',
                'producto_id' => $id,
                'ip' => $ip
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function visitasPorProducto() {
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