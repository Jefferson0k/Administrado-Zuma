<?php

namespace App\Http\Requests\CorporateEntity;

use Illuminate\Foundation\Http\FormRequest;

class StoreCorporateEntityRequests extends FormRequest{
    public function authorize(): bool{
        return true;
    }
    public function rules(){
        return [
            'nombre' => 'required|string|max:255',
            'ruc' => 'required|string|max:11|unique:corporate_entities,ruc',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'tipo_entidad' => 'required|string|max:100',
            'estado' => 'required|string|in:activo,inactivo',
            'pdf' => 'nullable|file|mimes:pdf',
        ];
    }

}
