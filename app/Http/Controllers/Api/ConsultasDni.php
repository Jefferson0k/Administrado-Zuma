<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ConsultasDni extends Controller{
    public function consultar(string $dni = null){
        if (empty($dni) || !preg_match('/^\d{8}$/', $dni)) {
            return response()->json(['error' => 'Debe proporcionar un DNI válido de 8 dígitos'], 400);
        }
        $token = env('CONSULTA_DNI_API_TOKEN');
        try {
            $response = Http::withHeaders([
                'Referer' => 'https://apis.net.pe/consulta-dni-api',
                'Authorization' => 'Bearer ' . $token,
            ])->get("https://apis.aqpfact.pe/api/dni/{$dni}");

            if ($response->failed() || empty($response->json())) {
                return response()->json(['error' => 'No se encontraron datos para el DNI proporcionado'], 404);
            }
            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al conectar con el servicio externo'], 500);
        }
    }
}
