<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Investor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
    public function show($id){
        $investor = Investor::select([
            'id',
            'name',
            'first_last_name',
            'second_last_name',
            'document',
        ])->find($id);
        if (!$investor) {
            return response()->json([
                'success' => false,
                'message' => 'Inversionista no encontrado'
            ], 404);
        }
        return response()->json([
            'success' => true,
            'data' => $investor
        ]);
    }
    public function update(Request $request, $id){
        $investor = Investor::find($id);
        if (!$investor) {
            return response()->json([
                'success' => false,
                'message' => 'Inversionista no encontrado',
            ], 404);
        }
        $validated = $request->validate([
            'alias' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:investors,email,' . $id,
            'telephone' => 'sometimes|string|max:20',
            'password' => 'nullable|string|min:6|confirmed',
        ]);
        $investor->fill($validated);
        if (!empty($validated['password'])) {
            $investor->password = Hash::make($validated['password']);
        }
        $wasEmailChanged = $investor->isDirty('email');
        $investor->save();
        // Enviar email de verificación si se cambió el correo
        if ($wasEmailChanged) {
            $investor->email_verified_at = null;
            $investor->save();
            $investor->sendEmailVerificationNotification();
        }

        return response()->json([
            'success' => true,
            'message' => 'Datos actualizados correctamente',
            'data' => $investor->only([
                'name',
                'first_last_name',
                'second_last_name',
                'document',
                'alias',
                'email',
                'telephone',
            ]),
        ]);
    }
}
