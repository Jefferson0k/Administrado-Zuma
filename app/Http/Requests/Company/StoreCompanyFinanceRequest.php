<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyFinanceRequest extends FormRequest{
    public function authorize(): bool{
        return true;
    }
    public function rules(): array{
        return [
            'company_id' => 'nullable|string|size:26|exists:companies,id',
            
            'sales_volume_pen' => 'nullable',
            'facturas_financiadas_pen' => 'nullable|integer|min:0',
            'monto_total_financiado_pen' => 'nullable|numeric|min:0',
            'pagadas_pen' => 'nullable|integer|min:0',
            // 'pendientes_pen' => 'nullable|integer|min:0',
            'plazo_promedio_pago_pen' => 'nullable|integer|min:0',

            'sales_volume_usd' => 'nullable|numeric|min:0',
            'facturas_financiadas_usd' => 'nullable|integer|min:0',
            'monto_total_financiado_usd' => 'nullable|numeric|min:0',
            'pagadas_usd' => 'nullable|integer|min:0',
            // 'pendientes_usd' => 'nullable|integer|min:0',
            'plazo_promedio_pago_usd' => 'nullable|integer|min:0',
        ];
    }
    public function messages(): array{
        return [
            'company_id.size' => 'El ID de la empresa debe tener 26 caracteres.',
            'company_id.exists' => 'La empresa seleccionada no existe.',
        ];
    }
}
