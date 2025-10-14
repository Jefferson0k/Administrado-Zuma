<?php

namespace App\Http\Requests\FixedRateDeposit;

use Illuminate\Foundation\Http\FormRequest;

class StoreZumaDepositRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nro_operation' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'bank_destino'  => ['required', 'string', 'exists:bank_account_destinos,id'],

            'voucher' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'payment_source' => 'nullable|string|max:255',
            'payment_schedules_id' => 'required|exists:payment_schedules,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nro_operation.required' => 'El número de operación es obligatorio.',
            'amount.required' => 'El monto es obligatorio.',
            'amount.numeric' => 'El monto debe ser un número válido.',
            'amount.min' => 'El monto debe ser mayor a 0.',
            'voucher.required' => 'El comprobante es obligatorio.',
            'voucher.file' => 'El comprobante debe ser un archivo válido.',
            'voucher.mimes' => 'El comprobante debe ser una imagen (JPG, JPEG, PNG) o PDF.',
            'voucher.max' => 'El comprobante no debe exceder 2MB.',
            'payment_schedules_id.required' => 'Debe seleccionar un cronograma de pagos.',
            'payment_schedules_id.exists' => 'El cronograma de pagos seleccionado no existe.',
        ];
    }
}