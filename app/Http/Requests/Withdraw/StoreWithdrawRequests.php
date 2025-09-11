<?php

namespace App\Http\Requests\Withdraw;

use Illuminate\Foundation\Http\FormRequest;

class StoreWithdrawRequests extends FormRequest{
    public function authorize(): bool{
        return true; // o usa Gate si quieres validar permisos aquí
    }
    public function rules(): array{
        return [
            'nro_operation' => 'required|min:1|regex:/^[A-Za-z0-9]+$/',
            'amount'        => 'required|numeric|min:0.01',
            'currency'      => 'required|string|in:USD,PEN', // ajusta a tus enums
            'deposit_pay_date' => 'required|date',
            'description'   => 'nullable|string|max:500',
            'purpouse'      => 'nullable|string|max:255',

            'file' => [
                'nullable',
                'mimes:jpeg,png,jpg,gif,webp',
                'max:10240', // 10MB
                'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
            ],

            'movement_id'     => 'required|exists:movements,id',
            'investor_id'     => 'required|exists:investors,id',
            'bank_account_id' => 'required|exists:bank_accounts,id',
        ];
    }

    public function messages(): array
    {
        return [
            'nro_operation.required' => 'Ingrese un número de operación',
            'nro_operation.regex'    => 'Ingrese un número de operación válido',
            'deposit_pay_date.required' => 'Ingrese una fecha de transferencia',
            'deposit_pay_date.date'  => 'Ingrese una fecha válida',
            'file.max'               => 'El archivo debe ser menor a 10MB',
        ];
    }
}
