<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequests extends FormRequest{
    public function authorize(): bool{
        return true;
    }

    public function rules(): array{
        $rules = [
            'name' => 'required|string|max:255',
            'risk' => 'required|numeric',
            'business_name' => 'required|string|max:255',
            'sector_id' => 'required',
            'document' => 'required|regex:/^[0-9]{8,11}$/',
            'link_web_page' => 'required|url',
            'description' => 'required|string',
            'incorporation_year' => 'required|numeric|gt:0|regex:/^[0-9]{4}$/|min:1901',
            'currency' => 'required|in:PEN,USD,BOTH',
        ];

        // Validación financiera según moneda
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
        return [
            'name.required' => 'El nombre es obligatorio',
            'risk.required' => 'El riesgo es obligatorio',
            'business_name.required' => 'La razón social es obligatoria',
            'sector_id.required' => 'El sector es obligatorio',
            'document.required' => 'El RUC es obligatorio',
            'document.regex' => 'El RUC debe tener 8 u 11 dígitos',
            'link_web_page.required' => 'La página web es obligatoria',
            'link_web_page.url' => 'La página web debe ser una URL válida',
            'description.required' => 'La descripción es obligatoria',
            'incorporation_year.required' => 'El año de constitución es obligatorio',
            'incorporation_year.regex' => 'El año de constitución debe tener 4 dígitos',
            'incorporation_year.min' => 'El año debe ser mayor a 1900',
            'currency.required' => 'La moneda es obligatoria',

            // PEN
            'sales_volume_pen.required' => 'El facturado en soles es obligatorio',
            'facturas_financiadas_pen.required' => 'Las facturas financiadas en soles son obligatorias',
            'monto_total_financiado_pen.required' => 'El monto total financiado en soles es obligatorio',
            'pagadas_pen.required' => 'Las facturas pagadas en soles son obligatorias',
            'pendientes_pen.required' => 'Las facturas pendientes en soles son obligatorias',
            'plazo_promedio_pago_pen.required' => 'El plazo promedio en soles es obligatorio',

            // USD
            'sales_volume_usd.required' => 'El facturado en dólares es obligatorio',
            'facturas_financiadas_usd.required' => 'Las facturas financiadas en dólares son obligatorias',
            'monto_total_financiado_usd.required' => 'El monto total financiado en dólares es obligatorio',
            'pagadas_usd.required' => 'Las facturas pagadas en dólares son obligatorias',
            'pendientes_usd.required' => 'Las facturas pendientes en dólares son obligatorias',
            'plazo_promedio_pago_usd.required' => 'El plazo promedio en dólares es obligatorio',
        ];
    }
}
