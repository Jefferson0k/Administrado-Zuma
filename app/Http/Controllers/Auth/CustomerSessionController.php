<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerSessionController extends Controller{
    public function login(Request $request){
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $customer = Customer::where('email', $request->email)->first();

        if (! $customer || ! Hash::check($request->password, $customer->password)) {
            return response()->json(['message' => 'Credenciales incorrectas'], 401);
        }

        if ($customer->status === 'not validated') {
            return response()->json(['message' => 'Tu cuenta aún no ha sido activada.'], 403);
        }

        $token = $customer->createToken('customer-token')->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso',
            'api_token' => $token,
            'customer' => $customer,
        ]);
    }
    public function logout(Request $request){
        $request->user('customer')->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada con éxito']);
    }
}