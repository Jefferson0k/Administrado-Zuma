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
            'document' => 'required|size:8|unique:customers,document',
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
            'message' => 'Cliente registrado. Verifique su correo electrónico.',
        ], 201);
    }
}
