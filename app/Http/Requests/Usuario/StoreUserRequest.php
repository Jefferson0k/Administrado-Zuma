<?php

namespace App\Http\Requests\Usuario;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest{
    public function authorize(): bool{
        return true;
    }
    public function rules(): array{
        return [
            'dni'       => 'required|digits:8|unique:users,dni',
            'name'      => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'email'     => 'required|string|email|max:120|unique:users,email',
            'password'  => 'required|string|min:8',
            'status'    => 'required|boolean',
            'cargo_id'  => ['required', 'exists:cargos,id'],
            'role_id'   => ['required', 'exists:roles,id'],
        ];
    }
    public function messages(){
        return [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.digits'   => 'El DNI debe tener exactamente 8 dígitos.',
            'dni.unique'   => 'Este DNI ya está registrado.',

            'name.required' => 'El nombre es obligatorio.',
            'name.string'   => 'El nombre debe ser texto.',
            'name.max'      => 'El nombre no debe exceder los 100 caracteres.',

            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.string'   => 'Los apellidos deben ser texto.',
            'apellidos.max'      => 'Los apellidos no deben exceder los 100 caracteres.',

            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email'    => 'Debe ingresar un correo electrónico válido.',
            'email.max'      => 'El correo no debe exceder los 120 caracteres.',
            'email.unique'   => 'Este correo ya está registrado.',

            'password.required' => 'La contraseña es obligatoria.',
            'password.min'      => 'La contraseña debe tener al menos 8 caracteres.',

            'status.required' => 'El estado es obligatorio.',
            'status.boolean'  => 'El estado debe ser verdadero o falso.',

            'cargo_id.required' => 'El cargo es obligatorio.',
            'cargo_id.exists'   => 'El cargo seleccionado no es válido.',

            'role_id.required' => 'El rol es obligatorio.',
            'role_id.exists'   => 'El rol seleccionado no es válido.',
        ];
    }
}
