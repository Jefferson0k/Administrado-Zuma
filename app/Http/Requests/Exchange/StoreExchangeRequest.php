<?php

namespace App\Http\Requests\Exchange;

use Illuminate\Foundation\Http\FormRequest;

class StoreExchangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy se encarga de autorizar
    }

    public function rules(): array
    {
        return [
            'exchange_rate_sell' => 'required|numeric|min:0',
            'exchange_rate_buy'  => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'exchange_rate_sell.required' => 'Debe ingresar la tasa de venta.',
            'exchange_rate_buy.required'  => 'Debe ingresar la tasa de compra.',
        ];
    }
}
