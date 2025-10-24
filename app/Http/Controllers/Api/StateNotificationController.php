<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StateNotification;

class StateNotificationController extends Controller
{
    //
    public function updateState(Request $request, $id) {
        
        try{
            
            $state = 1;

            $stateNotification = StateNotification::find($id);

            $stateNotification->update([
                'status' => $state
            ]);
            return response()->json([
                'success' => true,
                'data' => $stateNotification
            ]);
        } catch (Throwable $th) {
            DB::rollBack();
            Log::error("Error en registro: " . $th->getMessage());
            Log::error("Stack trace: " . $th->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar tu registro. Por favor intenta nuevamente.',
            ], 500);
        }; 
        
    }
}
