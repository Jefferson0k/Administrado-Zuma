<?php

namespace App\Http\Requests\PropertyLoanDetail;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyLoanDetailRequests extends FormRequest{
    public function authorize(){
        return true;
    }
    public function rules(): array
    {
        return [
            'property_id' => ['required', 'exists:properties,id'],
            'config_id' => ['required', 'exists:property_configuracions,id'],
            'investor_id' => ['required', 'exists:investors,id'],
            'ocupacion_profesion' => ['required', 'string'],
            'motivo_prestamo' => ['required', 'string'],
            'descripcion_financiamiento' => ['required', 'string'],
            'solicitud_prestamo_para' => ['required', 'string'],
            'garantia' => ['required', 'string'],
            'perfil_riesgo' => ['required', 'string'],
        ];
    }

}
