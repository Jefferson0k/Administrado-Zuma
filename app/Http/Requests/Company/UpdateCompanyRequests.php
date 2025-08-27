<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequests extends FormRequest{
    public function authorize(): bool{
        return true;
    }
    public function rules(): array{
        $rules = [
            'name' => 'required|string|max:255',
            'risk' => 'required|numeric',
            'business_name' => 'required|string|max:255',
            'sector_id' => 'required',
            'document' => 'required|regex:/^[0-9]{11}$/',
            'link_web_page' => 'required|url',
            'description' => 'required|string',
            'incorporation_year' => 'required|numeric|gt:0|regex:/^[0-9]{4}$/|min:1901',
            'currency' => 'required|in:PEN,USD,BOTH',
        ];
        if (in_array($this->input('currency'), ['PEN', 'BOTH'])) {
            $rules['sales_volume_pen'] = 'required|numeric|gt:0';
            $rules['facturas_financiadas_pen'] = 'required|numeric|min:0';
            $rules['monto_total_financiado_pen'] = 'required|numeric|min:0';
            $rules['pagadas_pen'] = 'required|numeric|min:0';
            $rules['pendientes_pen'] = 'required|numeric|min:0';
            $rules['plazo_promedio_pago_pen'] = 'required|numeric|min:0';
        }
        if (in_array($this->input('currency'), ['USD', 'BOTH'])) {
            $rules['sales_volume_usd'] = 'required|numeric|gt:0';
            $rules['facturas_financiadas_usd'] = 'required|numeric|min:0';
            $rules['monto_total_financiado_usd'] = 'required|numeric|min:0';
            $rules['pagadas_usd'] = 'required|numeric|min:0';
            $rules['pendientes_usd'] = 'required|numeric|min:0';
            $rules['plazo_promedio_pago_usd'] = 'required|numeric|min:0';
        }
        return $rules;
    }
    public function messages(): array{
        return (new StoreCompanyRequests())->messages();
    }
}
