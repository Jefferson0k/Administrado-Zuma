<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest{
    public function authorize(): bool{
        return true;
    }
    public function rules(): array{
        return [
            'id_payment_schedule' => 'required|exists:payment_schedules,id',
            'id_inversionista' => 'required|exists:investors,id',
            'moneda' => 'required|string|in:PEN,USD',
            'monto' => 'required|numeric|min:0.01',
        ];
    }
}
