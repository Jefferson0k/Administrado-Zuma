<?php

namespace App\Http\Controllers\Panel;

use App\Models\VisitaProducto;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class VisitaProductoController extends Controller{
    public function registrar($id, Request $request){
        try {
            $ip = $this->getRealIpAddr($request);
            Log::info('Intento de registro de visita', [
                'producto_id' => $id,
                'ip' => $ip,
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all()
            ]);
            $yaVisitado = VisitaProducto::where('ip', $ip)
                            ->where('producto_id', $id)
                            ->exists();
            Log::info('Verificación de visita existente', [
                'producto_id' => $id,
                'ip' => $ip,
                'ya_visitado' => $yaVisitado
            ]);
            if (!$yaVisitado) {
                $visita = VisitaProducto::create([
                    'ip' => $ip,
                    'producto_id' => $id,
                ]);
                Log::info('Visita registrada exitosamente', [
                    'visita_id' => $visita->id,
                    'producto_id' => $id,
                    'ip' => $ip
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Visita registrada',
                    'producto_id' => $id,
                    'ip' => $ip,
                    'visita_id' => $visita->id
                ], 201);
            }
            return response()->json([
                'success' => false,
                'message' => 'Esta IP ya ha registrado una visita a este producto',
                'producto_id' => $id,
                'ip' => $ip
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al registrar visita', [
                'producto_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    private function getRealIpAddr(Request $request){
        if ($request->hasHeader('HTTP_CF_CONNECTING_IP')) {
            return $request->header('HTTP_CF_CONNECTING_IP');
        }
        if ($request->hasHeader('HTTP_CLIENT_IP')) {
            return $request->header('HTTP_CLIENT_IP');
        }        
        if ($request->hasHeader('HTTP_X_FORWARDED_FOR')) {
            $ips = explode(',', $request->header('HTTP_X_FORWARDED_FOR'));
            return trim($ips[0]);
        }        
        if ($request->hasHeader('HTTP_X_FORWARDED')) {
            return $request->header('HTTP_X_FORWARDED');
        }        
        if ($request->hasHeader('HTTP_X_CLUSTER_CLIENT_IP')) {
            return $request->header('HTTP_X_CLUSTER_CLIENT_IP');
        }        
        if ($request->hasHeader('HTTP_FORWARDED_FOR')) {
            return $request->header('HTTP_FORWARDED_FOR');
        }        
        if ($request->hasHeader('HTTP_FORWARDED')) {
            return $request->header('HTTP_FORWARDED');
        }        
        return $request->ip();
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
    public function debug(){
        $visitas = VisitaProducto::with('producto')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $visitas,
            'total' => $visitas->count()
        ]);
    }
    public function debugPorIp(Request $request){
        $ip = $this->getRealIpAddr($request);
        $visitas = VisitaProducto::where('ip', $ip)
            ->with('producto')
            ->get();
        return response()->json([
            'success' => true,
            'ip_detectada' => $ip,
            'visitas' => $visitas,
            'total' => $visitas->count()
        ]);
    }
}