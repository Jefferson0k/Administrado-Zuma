<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredCustomerController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'first_last_name' => 'required|string|max:255',
            'second_last_name' => 'required|string|max:255',
            'document' => [
                'required',
                'unique:customers,document',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->tipo_documento_id == 1 && strlen($value) !== 8) {
                        // 1 = DNI
                        $fail('El DNI debe tener exactamente 8 dígitos.');
                    }

                    if ($request->tipo_documento_id == 2 && (strlen($value) < 9 || strlen($value) > 20)) {
                        // 2 = Carnet de extranjería
                        $fail('El Carnet de extranjería debe tener entre 9 y 20 dígitos.');
                    }
                },
            ],
            'email' => 'required|email|unique:customers,email',
            'telephone' => 'required|string|max:15',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $customer = Customer::create([
            'name' => $request->name,
            'first_last_name' => $request->first_last_name,
            'second_last_name' => $request->second_last_name,
            'document' => $request->document,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($customer));

        return response()->json([
            'success' => true,
            'message' => 'Cliente registrado. Verifique su correo electrónico.',
            'data' => [
                'userId' => $customer->id,
                'email' => $customer->email
            ],
        ], 201);
    }
}
