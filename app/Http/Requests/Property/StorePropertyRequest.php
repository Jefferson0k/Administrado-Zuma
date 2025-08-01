<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest{
    public function authorize(): bool{
        return true;
    }
    public function rules(): array{
        return [
            'nombre'          => 'required|string|max:255',
            'departamento'    => 'required|string|max:255',
            'provincia'       => 'required|string|max:255',
            'distrito'        => 'required|string|max:255',
            'direccion'       => 'required|string|max:255',
            'descripcion'     => 'nullable|string',
            
            'valor_estimado'  => 'required|numeric|min:0',
            'valor_subasta'   => 'nullable|numeric|min:0',
            'valor_requerido' => 'required|numeric|min:0',
            'currency_id'     => 'required|exists:currencies,id',
            'investor_id'     => 'required',
            'imagenes'        => 'nullable|array',
            'imagenes.*'      => 'nullable|image|max:2048',
        ];
    }
}
