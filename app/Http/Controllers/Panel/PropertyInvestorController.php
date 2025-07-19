<?php

namespace App\Http\Controllers\Panel;

use App\Models\Property;
use App\Models\PropertyInvestor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\Subastas\Investor\InversionResource;
use App\Http\Resources\Subastas\Investor\PropertyInvestorResource;
use App\Models\Deadlines;
use App\Models\Investor;
use App\Models\PaymentSchedule;

class PropertyInvestorController extends Controller{

    public function store(Request $request){
        $request->validate([
            'property_id' => 'required|exists:properties,id',
            'deadline_id' => 'required|exists:deadlines,id',
        ]);

        try {
            DB::beginTransaction();
            
            $investor = Auth::user();
            if (!$investor) {
                return response()->json(['message' => 'Investor no autenticado.'], 401);
            }
            
            if (!$investor instanceof Investor) {
                return response()->json([
                    'message' => 'El usuario autenticado no es un inversionista.',
                    'error' => 'Solo los inversionistas pueden adquirir propiedades.'
                ], 403);
            }
            
            $property = Property::findOrFail($request->property_id);
            $deadline = Deadlines::findOrFail($request->deadline_id);
            
            if ($property->estado === 'adquirido') {
                return response()->json(['message' => 'La propiedad ya fue adquirida.'], 400);
            }
            
            $existingInvestment = PropertyInvestor::where('property_id', $property->id)
                ->where('investor_id', $investor->id)
                ->first();
                
            if ($existingInvestment) {
                return response()->json(['message' => 'Ya tienes una inversión en esta propiedad.'], 400);
            }
            
            $propertyInvestor = PropertyInvestor::create([
                'property_id' => $property->id,
                'investor_id' => $investor->id,
                'amount' => $property->valor_estimado,
                'status' => 'activo',
            ]);
            
            $property->estado = 'adquirido';
            $property->deadlines_id = $deadline->id;
            $property->save();
            
            $this->generatePaymentSchedule($propertyInvestor, $property, $deadline);
            
            DB::commit();
            
            return response()->json([
                'message' => 'Propiedad adquirida correctamente.',
                'data' => [
                    'property_id' => $property->id,
                    'investor_id' => $investor->id,
                    'amount' => $property->valor_estimado,
                    'deadline_id' => $deadline->id,
                    'plazo_meses' => $deadline->duracion_meses
                ]
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Recurso no encontrado.',
                'error' => 'La propiedad o plazo especificado no existe.'
            ], 404);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al adquirir propiedad.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    private function generatePaymentSchedule($propertyInvestor, $property, $deadline){
        $capital = $property->valor_estimado;
        $tem = $property->tem;
        $temConIgv = $tem * 1.18;
        $temDecimal = $temConIgv / 100;
        $n = $deadline->duracion_meses;
        
        $cuota = $capital * ($temDecimal * pow(1 + $temDecimal, $n)) /
                (pow(1 + $temDecimal, $n) - 1);
        
        $saldoInicial = $capital;
        
        for ($numeroCuota = 1; $numeroCuota <= $n; $numeroCuota++) {
            $intereses = $saldoInicial * $temDecimal;
            $capitalAmortizado = $cuota - $intereses;
            $saldoFinal = $saldoInicial - $capitalAmortizado;
            
            $igv = $intereses * 0.18;
            $cuotaNeta = $cuota;
            $totalCuota = $cuotaNeta + $igv;
            
            $fechaVencimiento = now()->addMonths($numeroCuota)->startOfMonth();
            
            PaymentSchedule::create([
                'property_investor_id' => $propertyInvestor->id,
                'cuota' => $numeroCuota,
                'vencimiento' => $fechaVencimiento,
                'saldo_inicial' => round($saldoInicial, 2),
                'capital' => round($capitalAmortizado, 2),
                'intereses' => round($intereses, 2),
                'cuota_neta' => round($cuotaNeta, 2),
                'igv' => round($igv, 2),
                'total_cuota' => round($totalCuota, 2),
                'saldo_final' => round($saldoFinal, 2),
                'estado' => 'pendiente'
            ]);
            
            $saldoInicial = $saldoFinal;
        }
    }
    public function inversion(){
        $investor = Auth::guard('investor')->user();
        $ultimaInversion = PropertyInvestor::with([
            'property.currency',
            'configuracion.plazo',
            'paymentSchedules'
        ])
        ->where('investor_id', $investor->id)
        ->where('status', 'pendiente')
        ->latest()
        ->first();
        if (!$ultimaInversion) {
            return response()->json(['message' => 'No se encontró inversión pendiente'], 404);
        }
        return response()->json(new InversionResource($ultimaInversion));
    }
    private function getEstadoFiltro($userType){
        switch ($userType) {
            case 'inversionista':
                return [1];
            case 'cliente':
                return [2];
            case 'mixto':
                return [1, 2];
            default:
                return [];
        }
    }
    public function ranquiSubastas(){
        $ranking = PropertyInvestor::with('property')
            ->whereHas('property', function ($query) {
                $query->where('estado', 'subastada');
            })
            ->select('property_id', 'amount')
            ->orderByDesc('amount')
            ->limit(6)
            ->get();
        return response()->json(PropertyInvestorResource::collection($ranking));
    }

}

