<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Models\Investor;

class ConsultasDni extends Controller{
    public function consultar(string $dni = null){
        if (empty($dni) || !preg_match('/^\d{8}$/', $dni)) {
            return response()->json(['success' => false, 'message' => 'Debe proporcionar un DNI válido de 8 dígitos'], 400);
        }
        $investor = Investor::where('document', $dni)->first();
        if ($investor) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $investor->id,
                    'apellido_paterno' => $investor->first_last_name,
                    'apellido_materno' => $investor->second_last_name,
                    'nombres' => $investor->name,
                ],
                'message' => 'Datos obtenidos localmente.'
            ]);
        }
        $token = env('CONSULTA_DNI_API_TOKEN');
        try {
            $response = Http::withHeaders([
                'Referer' => 'https://apis.net.pe/consulta-dni-api',
                'Authorization' => 'Bearer ' . $token,
            ])->get("https://apis.aqpfact.pe/api/dni/{$dni}");

            if ($response->failed() || empty($response->json('data'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron datos para el DNI proporcionado.'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => [
                    'apellido_paterno' => $response['data']['apellido_paterno'],
                    'apellido_materno' => $response['data']['apellido_materno'],
                    'nombres' => $response['data']['nombres'],
                ],
                'message' => 'Datos obtenidos correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al conectar con el servicio externo.'
            ], 500);
        }
    }
}
