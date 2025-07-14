<?php

namespace App\Http\Requests\PaymentFrequency;

use Illuminate\Foundation\Http\FormRequest;
class UpdatePaymentFrequencyRequests extends FormRequest{
    public function authorize(): bool{
        return true;
    }
    public function rules(): array{
        return [
            'nombre' => 'required|string|max:255',
            'dias' => 'required|integer|min:1',
        ];
    }
}
