<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Investor;
use Illuminate\Http\Request;

class InvestorController extends Controller{
    public function store(Request $request){
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'first_last_name' => 'required|string|max:255',
            'second_last_name' => 'required|string|max:255',
            'document' => 'required|string|max:20|unique:investors,document',
        ]);
        $validated['type'] = 'cliente';
        $investor = Investor::create($validated);
        return response()->json([
            'success' => true,
            'message' => 'Inversionista creado correctamente.',
            'data' => $investor
        ], 201);
    }
    public function resendEmailVerification(Request $request, string $id)
    {
        try {
            $investor = Investor::find(htmlspecialchars($id));
            if (!$investor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado.',
                ], 404);
            }
            // check if the current investor has already verified their email
            if ($investor->hasVerifiedEmail()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Correo ya esta verificado.'
                ], 307);
            }

            $investor->sendEmailVerificationNotification();

            return response()->json([
                'success' => true,
                'message' => 'Se ha enviado un correo de verificación.',
                'data' => $investor
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
