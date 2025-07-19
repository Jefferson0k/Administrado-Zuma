<?php

namespace App\Http\Requests\PropertyReservationRequest;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyReservationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'property_id' => 'required',
            'config_id' => 'required',
            'amount' => 'required|numeric|min:0',
        ];
    }
    public function messages()
    {
        return [
            'property_id.required' => 'El ID de la propiedad es requerido.',
            'property_id.exists' => 'La propiedad no existe.',
            'amount.required' => 'El monto es requerido.',
            'amount.numeric' => 'El monto debe ser un número.',
            'amount.min' => 'El monto debe ser mayor a 0.',
        ];
    }
}