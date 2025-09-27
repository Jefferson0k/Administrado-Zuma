<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

class ConsultasRucController extends Controller
{
     public function consultar(string $ruc = null): JsonResponse
    {
        if (empty($ruc) || !preg_match('/^\d{11}$/', $ruc)) {
            return response()->json([
                'success' => false,
                'message' => 'Debe proporcionar un RUC válido de 11 dígitos',
            ], 400);
        }

        // ✅ Validar existencia en la base de datos solo si viene el flag
        if (request()->boolean('validate_exists', false)) {
            $exists = Company::where('document', $ruc)->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este RUC ya está registrado en el sistema',
                    'exists'  => true,
                ], 409);
            }
        }

        $token = env('CONSULTA_RUC_API_TOKEN');

        try {
            $response = Http::withToken($token)
                ->get('https://api.apis.net.pe/v2/sunat/ruc/full', [
                    'numero' => $ruc,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return response()->json([
                    'success'      => true,
                    'exists'       => false,
                    'ruc'          => $data['numeroDocumento'] ?? $ruc,
                    'razonSocial'  => $data['razonSocial'] ?? null,
                    'direccion'    => $data['direccion'] ?? null,
                    'data'         => $data, // todo el objeto original
                ]);
            }

            if ($response->status() === 404) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró información para el RUC proporcionado',
                ], 404);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error en la consulta al servicio externo',
                'status'  => $response->status(),
                'body'    => $response->body(),
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar el RUC',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
