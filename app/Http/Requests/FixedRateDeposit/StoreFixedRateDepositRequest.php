<?php

namespace App\Http\Requests\FixedRateDeposit;

use Illuminate\Foundation\Http\FormRequest;

class StoreFixedRateDepositRequest extends FormRequest{
    public function authorize(): bool{
        return true;
    }
    public function rules(): array{
        return [
            'nro_operation' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'voucher' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf'],
            'payment_source' => ['nullable', 'string', 'max:255'],
            'fixed_term_investment_id' => [
                'required',
                'exists:fixed_term_investments,id',
            ],
        ];
    }
    public function messages(): array{
        return [
            'fixed_term_investment_id.required' => 'La inversión de plazo fijo es obligatoria.',
            'fixed_term_investment_id.exists' => 'La inversión de plazo fijo seleccionada no existe.',
        ];
    }
}
