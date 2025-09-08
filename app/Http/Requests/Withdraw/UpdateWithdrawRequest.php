<?php

namespace App\Http\Requests\Withdraw;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWithdrawRequest extends FormRequest{
    public function authorize(): bool{
        return true;
    }
    public function rules(): array{
        return [
            'nro_operation' => 'sometimes|required|min:1|regex:/^[A-Za-z0-9]+$/',
            'amount'        => 'sometimes|required|numeric|min:0.01',
            'currency'      => 'sometimes|required|string|in:USD,PEN',
            'deposit_pay_date' => 'sometimes|required|date',
            'description'   => 'nullable|string|max:500',
            'purpouse'      => 'nullable|string|max:255',
            'file' => [
                'nullable',
                'mimes:jpeg,png,jpg,gif,webp',
                'max:10240',
                'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
            ],
            'movement_id'     => 'sometimes|required|exists:movements,id',
            'investor_id'     => 'sometimes|required|exists:investors,id',
            'bank_account_id' => 'sometimes|required|exists:bank_accounts,id',
        ];
    }
}
