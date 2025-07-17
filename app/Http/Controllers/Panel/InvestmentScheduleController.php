<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\InvestorPropertyStatus;
use App\Models\Property;
use App\Models\PropertyInvestor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvestmentScheduleController extends Controller {
    public function store(Request $request){
        $request->validate([
            'property_id' => ['required', 'exists:properties,id'],
        ]);
        $investorId = Auth::guard('sanctum')->user()->id;
        $status = InvestorPropertyStatus::updateOrCreate(
            [
                'investor_id' => $investorId,
                'property_id' => $request->property_id,
            ],
            [
                'estado' => 'pendiente'
            ]
        );
        $property = Property::find($request->property_id);
        if ($property && $property->estado !== 'en_espera') {
            $property->estado = 'en_espera';
            $property->save();
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Postulación registrada y propiedad actualizada correctamente.',
            'data' => $status,
        ]);
    }
    public function acepta(Request $request){
        $request->validate([
            'property_id' => ['required', 'exists:properties,id'],
            'config_id'   => ['required', 'exists:property_investors,config_id'],
        ]);
        $investorId = Auth::guard('sanctum')->user()->id;
        $status = InvestorPropertyStatus::where('investor_id', $investorId)
            ->where('property_id', $request->property_id)
            ->first();
        if (!$status) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontró postulación previa del inversionista a esta propiedad.',
            ], 404);
        }
        $status->estado = 'aceptado';
        $status->save();

        $property = Property::find($request->property_id);
        if ($property) {
            $property->estado = 'adquirido';
            $property->save();
        }
        $propertyInvestor = PropertyInvestor::where('config_id', $request->config_id)->first();
        if ($propertyInvestor) {
            $propertyInvestor->investor_id = $investorId;
            $propertyInvestor->save();
        }
        return response()->json([
            'status' => 'success',
            'message' => 'El inversionista fue aceptado correctamente.',
        ]);
    }
    public function rechazado(Request $request){
        $request->validate([
            'property_id' => ['required', 'exists:properties,id'],
        ]);
        $investorId = Auth::guard('sanctum')->user()->id;
        $status = InvestorPropertyStatus::where('investor_id', $investorId)
            ->where('property_id', $request->property_id)
            ->first();
        if (!$status) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontró postulación previa del inversionista a esta propiedad.',
            ], 404);
        }
        $status->estado = 'rechazado';
        $status->save();
        $property = Property::find($request->property_id);
        if ($property) {
            $property->estado = 'activa';
            $property->save();
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Postulación rechazada y propiedad reactivada correctamente.',
        ]);
    }
}
