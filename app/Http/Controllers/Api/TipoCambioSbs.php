<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class TipoCambioSbs extends Controller{
    public function TipoCambioSbs(): JsonResponse{
        return response()->json([
            'tipo_cambio' => 3.75
        ]);
    }
}
