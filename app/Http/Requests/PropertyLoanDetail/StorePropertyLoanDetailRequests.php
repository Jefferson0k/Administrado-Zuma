<?php

namespace App\Http\Requests\PropertyLoanDetail;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyLoanDetailRequests extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id' => ['required', 'exists:properties,id'],
            'config_id' => ['required', 'exists:property_configuracions,id'],
            'investor_id' => ['required', 'exists:investors,id'],
            // 'ocupacion_profesion' => ['nullable', 'string', 'max:200'],
            'empresa_tasadora' => ['nullable', 'string', 'max:150'],
            'motivo_prestamo' => ['nullable', 'string', 'max:300'],
            'descripcion_financiamiento' => ['nullable', 'string', 'max:500'],
            'solicitud_prestamo_para' => ['nullable', 'string', 'max:500'],
            'monto_tasacion' => ['nullable', 'integer', 'min:0'],
            'porcentaje_prestamo' => ['nullable', 'integer', 'min:0', 'max:100'],
            'monto_invertir' => ['nullable', 'integer', 'min:0'],
            'monto_prestamo' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
