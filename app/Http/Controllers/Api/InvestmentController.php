<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\InvestmentSimulatorService;

class InvestmentController extends Controller
{
    protected $simulator;

    public function __construct(InvestmentSimulatorService $simulator)
    {
        $this->simulator = $simulator;
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'monto' => 'required|numeric|min:1',
            'plazo' => 'required|integer|min:1',
        ]);

        $monto = $validated['monto'];
        $plazo = $validated['plazo'];

        $resultado = $this->simulator->simular($monto, $plazo);

        return response()->json([
            'data' => $resultado
        ]);
    }
}
