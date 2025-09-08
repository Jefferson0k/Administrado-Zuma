<?php

namespace App\Http\Requests\Exchange;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExchangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy se encarga de autorizar
    }

    public function rules(): array
    {
        return [
            'currency'           => 'sometimes|string|in:USD,PEN',
            'exchange_rate_sell' => 'sometimes|numeric|min:0',
            'exchange_rate_buy'  => 'sometimes|numeric|min:0',
            'status'             => 'sometimes|in:active,inactive',
        ];
    }

    public function messages(): array
    {
        return [
            'currency.in'              => 'La moneda seleccionada no es válida.',
            'exchange_rate_sell.min'   => 'La tasa de venta debe ser mayor a 0.',
            'exchange_rate_buy.min'    => 'La tasa de compra debe ser mayor a 0.',
            'status.in'                => 'El estado debe ser activo o inactivo.',
        ];
    }
}
