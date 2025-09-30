<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\DetalleInversionistaHipoteca;
use Illuminate\Http\Request;

class DetalleInversionistaHipotecaController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'investor_id' => 'required|exists:investors,id',
            'fuente_ingreso' => 'required|string|max:255',
            'profesion_ocupacion' => 'required|string|max:255',
            'ingreso_promedio' => 'required|numeric|min:0',
        ]);

        $detalle = DetalleInversionistaHipoteca::updateOrCreate(
            ['investor_id' => $request->investor_id], // condición de búsqueda
            [
                'fuente_ingreso' => $request->fuente_ingreso,
                'profesion_ocupacion' => $request->profesion_ocupacion,
                'ingreso_promedio' => $request->ingreso_promedio,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Detalle del inversionista guardado correctamente.',
            'data' => $detalle,
        ]);
    }
}
