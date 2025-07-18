<?php

namespace App\Http\Requests\PropertyReservations;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id' => 'required|exists:properties,id',
            'config_id' => 'nullable|exists:property_configuracions,id',
            'amount' => 'required|numeric|min:0.01|max:999999.99',
        ];
    }

    public function messages(): array
    {
        return [
            'property_id.required' => 'El ID de la propiedad es obligatorio.',
            'property_id.exists' => 'La propiedad seleccionada no existe.',
            'config_id.exists' => 'La configuración seleccionada no existe.',
            'amount.required' => 'El monto es obligatorio.',
            'amount.numeric' => 'El monto debe ser un número.',
            'amount.min' => 'El monto debe ser mayor a 0.',
            'amount.max' => 'El monto no puede exceder 999,999.99.',
        ];
    }
}