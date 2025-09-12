<?php

namespace App\Http\Requests\Investor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvestorRequest extends FormRequest{
    public function authorize(): bool{
        return true;
    }
    public function rules(): array{
        return [
            'name' => 'required|string|min:2|max:255',
            'first_last_name' => 'required|string|min:2|max:255',
            'second_last_name' => 'required|string|min:2|max:255',
            'alias' => 'nullable|string|min:2|max:100',
            'document' => 'required|numeric|digits:8|unique:investors,document',
            'email' => [
                'required',
                'email',
                Rule::unique('investors', 'email'),
            ],
            'password' => [
                'required',
                'string',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/'
            ],
            'telephone' => 'required|string|min:6|max:15',
        ];
    }
    public function messages(){
        return [
            'first_last_name.required' => 'Apellido paterno es obligatorio.',
            'first_last_name.min' => 'Apellido paterno debe tener al menos 2 caracteres.',
            'first_last_name.max' => 'Apellido paterno no puede tener más de 255 caracteres.',
            'second_last_name.required' => 'Apellido materno es obligatorio.',
            'second_last_name.min' => 'Apellido materno debe tener al menos 2 caracteres.',
            'second_last_name.max' => 'Apellido materno no puede tener más de 255 caracteres.',
            'document.required' => 'Documento es obligatorio.',
            'document.numeric' => 'Documento debe ser numérico.',
            'document.digits' => 'Documento debe tener 8 dígitos.',
            'document.unique' => 'Este documento ya se encuentra registrado.',
            'email.required' => 'Correo electrónico es obligatorio.',
            'email.email' => 'Correo electrónico debe ser válido.',
            'email.unique' => 'Este correo ya se encuentra registrado.',
            'password.required' => 'Contraseña es obligatoria.',
            'password.regex' => 'La Contraseña  debe tener al menos 8 caracteres, una mayúscula, un número y un carácter especial.',
            'telephone.required' => 'El número de teléfono es obligatorio.',
            'telephone.min' => 'El número de teléfono debe tener al menos 6 caracteres.',
            'telephone.max' => 'El número de teléfono debe tener al menos 15 caracteres.',

            'required' => 'El :attribute es obligatorio.',
            'min' => 'El :attribute debe tener al menos :min caracteres.',
            'max' => 'El :attribute no puede tener más de :max caracteres.',
            'numeric' => 'El :attribute debe ser numérico.',
            'digits_between' => 'El :attribute debe tener entre :min y :max dígitos.',
            'email' => 'El :attribute debe ser un correo electrónico válido.',
            'string' => 'El :attribute debe ser una cadena de caracteres.',
        ];
    }
}
