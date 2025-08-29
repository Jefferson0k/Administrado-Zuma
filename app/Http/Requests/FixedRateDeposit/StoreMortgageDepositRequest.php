<?php

namespace App\Http\Requests\FixedRateDeposit;

use Illuminate\Foundation\Http\FormRequest;

class StoreMortgageDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nro_operation'            => 'required|string|max:255',
            'amount'                   => 'required|numeric|min:0.01',
            'voucher'                  => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'payment_source'           => 'nullable|string|max:255',
            'property_reservations_id' => 'required',
        ];
    }
}
