<?php

namespace App\Http\Requests\Solicitud;

use Illuminate\Foundation\Http\FormRequest;

class StoreSolicitudRequest extends FormRequest{
    public function authorize(): bool{
        return true;
    }
    public function rules(): array{
        return [
            'codigo'         => ['required', 'string', 'max:255', 'unique:solicitudes,codigo'],
            'investor_id'    => ['required', 'exists:investors,id'],
            'valor_general'  => ['required', 'integer', 'min:0'],
            'valor_requerido'=> ['required', 'integer', 'min:0'],
            'type'           => ['required', 'in:inversionista,cliente,mixto'],
        ];
    }
    public function messages(): array{
        return [
            'codigo.required'          => 'El código es obligatorio.',
            'codigo.unique'            => 'El código ya está en uso.',
            'investor_id.required'     => 'El inversor es obligatorio.',
            'investor_id.exists'       => 'El inversor seleccionado no existe.',
            'valor_general.required'   => 'El valor general es obligatorio.',
            'valor_general.integer'    => 'El valor general debe ser un número entero.',
            'valor_requerido.required' => 'El valor requerido es obligatorio.',
            'valor_requerido.integer'  => 'El valor requerido debe ser un número entero.',
            'type.required'            => 'El tipo de solicitud es obligatorio.',
            'type.in'                  => 'El tipo debe ser inversionista, cliente o mixto.',
        ];
    }
}
