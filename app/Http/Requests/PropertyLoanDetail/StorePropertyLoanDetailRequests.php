<?php

namespace App\Http\Requests\PropertyLoanDetail;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyLoanDetailRequests extends FormRequest{
    public function authorize(){
        return true;
    }
    public function rules(){
        return [
            'property_id' => 'required|exists:properties,id',
            'customer_id' => 'required|exists:customers,id',
            'ocupacion_profesion' => 'nullable|string|max:255',
            'motivo_prestamo' => 'nullable|string|max:255',
            'descripcion_financiamiento' => 'nullable|string',
            'solicitud_prestamo_para' => 'nullable|string|max:255',
            'garantia' => 'nullable|string|max:255',
            'perfil_riesgo' => 'nullable|string|max:255',
        ];
    }
}
