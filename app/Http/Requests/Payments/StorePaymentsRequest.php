<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentsRequest extends FormRequest{
    public function authorize(): bool{
        return true;
    }
    public function rules(): array{
        return [
            'bank' => [
                'required',
                'string',
                'exists:bank_accounts,id'
            ],
            'bank_destino'  => ['nullable', 'string', 'exists:bank_account_destinos,id'],
            'amount' => 'required|numeric|min:1',
            'nro_operation' => 'required|string',
            'voucher' => 'nullable|mimes:jpeg,png,jpg,pdf',
        ];
    }
    public function messages(): array{
        return [
            'bank.exists' => 'La cuenta bancaria seleccionada no existe.',
            'bank.required' => 'Selecciona tu cuenta bancaria.',
            'amount.required' => 'Ingresa un monto.',
            'amount.numeric' => 'El monto debe ser numérico.',
            'amount.min' => 'El monto debe ser mayor a 1.',
            'nro_operation.required' => 'El campo :attribute es requerido.',
            'voucher.nullable' => 'El campo :attribute es requerido.',
            'voucher.mimes' => 'El tipo de archivo del documento es incorrecto.',
        ];
    }
}
