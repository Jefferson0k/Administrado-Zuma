<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $invoiceId = $this->route('invoice')?->id ?? null;
        $companyId = $this->input('company_id');

        return [
            'company_id' => ['required', 'exists:companies,id'],
            'currency' => ['required', 'string', 'in:PEN,USD'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'financed_amount_by_garantia' => ['required', 'numeric', 'gt:0', 'lte:amount'],
            // financed_amount se calcula en el servicio → opcional
            'financed_amount' => ['nullable', 'numeric'],
            'paid_amount' => ['nullable', 'numeric', 'gte:0'],
            'rate' => ['required', 'numeric', 'gt:0', 'lte:6'],
            // 'due_date' => ['required', 'date'], // ❌ lo genera el sistema
            'estimated_pay_date' => ['required', 'date'],
            'loan_number' => [
                'nullable',
                'string',
                Rule::unique('invoices', 'loan_number')
                    ->where('company_id', $companyId)
                    ->ignore($invoiceId)
            ],
            'invoice_number' => [
                'nullable',
                'string',
                Rule::unique('invoices', 'invoice_number')
                    ->where('company_id', $companyId)
                    ->ignore($invoiceId)
            ],
            'ruc_proveedor' => ['nullable', 'regex:/^[0-9]{11}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'Debe seleccionar una empresa.',
            'company_id.exists' => 'La empresa seleccionada no existe.',
            'currency.required' => 'La moneda es obligatoria.',
            'currency.in' => 'La moneda debe ser PEN o USD.',
            'amount.required' => 'El monto de la factura es obligatorio.',
            'amount.gt' => 'El monto de la factura debe ser mayor a 0.',
            'financed_amount_by_garantia.required' => 'El monto financiado por garantía es obligatorio.',
            'financed_amount_by_garantia.lte' => 'El monto financiado por garantía no puede ser mayor al monto de la factura.',
            'rate.required' => 'La tasa es obligatoria.',
            'rate.lte' => 'La tasa no puede superar el 6%.',
            'estimated_pay_date.required' => 'La fecha estimada de pago es obligatoria.',
            'ruc_proveedor.regex' => 'El RUC del proveedor debe tener 11 dígitos.',
            'loan_number.unique' => 'El número de préstamo ya existe para esta empresa.',
            'invoice_number.unique' => 'El número de factura ya existe para esta empresa.',
        ];
    }
}
