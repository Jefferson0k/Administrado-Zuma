<?php

namespace App\Http\Controllers\Panel;

use App\Models\VisitaProducto;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VisitaProductoController extends Controller
{
    public function registrar($id, Request $request){
        try {
            $ip = $request->input('ip') ?? $request->header('X-Forwarded-For') ?? $request->ip();
            $userAgent = $request->userAgent();
            $yaVisitado = VisitaProducto::where('producto_id', $id)
                ->where('ip', $ip)
                ->exists();
            if ($yaVisitado) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya se registró esta IP para este producto',
                    'producto_id' => $id,
                    'ip' => $ip,
                ], 200);
            }
            VisitaProducto::create([
                'producto_id' => $id,
                'ip' => $ip,
                'user_agent' => $userAgent,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Visita registrada',
                'producto_id' => $id,
                'ip' => $ip,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function visitasPorProducto(){
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
