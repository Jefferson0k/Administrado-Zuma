<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        return [
            // Campos de Solicitud (solo los editables)
            'valor_requerido' => 'required|numeric|min:0',
            'currency_id' => 'required|exists:currencies,id',
            
            // Array de propiedades
            'properties' => 'required|array|min:1',
            'properties.*.id' => 'nullable|string', // Puede ser null si es nueva
            'properties.*.nombre' => 'required|string|max:255',
            'properties.*.departamento' => 'required|string|max:255',
            'properties.*.provincia' => 'required|string|max:255',
            'properties.*.distrito' => 'required|string|max:255',
            'properties.*.direccion' => 'required|string|max:255',
            'properties.*.descripcion' => 'nullable|string',
            'properties.*.pertenece' => 'nullable|string|max:255',
            'properties.*.id_tipo_inmueble' => 'nullable|exists:tipo_inmueble,id_tipo_inmueble',
            'properties.*.valor_estimado' => 'required|numeric|min:0',
            
            // Campos de ubicación (IDs)
            'properties.*.departamento_id' => 'nullable|string|max:10',
            'properties.*.provincia_id' => 'nullable|string|max:10',
            'properties.*.distrito_id' => 'nullable|string|max:10',
            
            // Imágenes (opcionales en update)
            'properties.*.imagenes' => 'nullable|array',
            'properties.*.imagenes.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'properties.*.descriptions' => 'nullable|array',
            'properties.*.descriptions.*' => 'nullable|string|max:255',
            
            // Imágenes a eliminar
            'properties.*.imagenes_eliminar' => 'nullable|array',
            'properties.*.imagenes_eliminar.*' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'valor_requerido.required' => 'El valor requerido es obligatorio.',
            'valor_requerido.numeric' => 'El valor requerido debe ser un número.',
            
            'currency_id.required' => 'La moneda es obligatoria.',
            'currency_id.exists' => 'La moneda seleccionada no es válida.',
            
            'properties.required' => 'Debe haber al menos una propiedad.',
            'properties.min' => 'Debe haber al menos una propiedad.',
            
            'properties.*.nombre.required' => 'El nombre de la propiedad es obligatorio.',
            'properties.*.departamento.required' => 'El departamento es obligatorio.',
            'properties.*.provincia.required' => 'La provincia es obligatoria.',
            'properties.*.distrito.required' => 'El distrito es obligatorio.',
            'properties.*.direccion.required' => 'La dirección es obligatoria.',
            'properties.*.id_tipo_inmueble.exists' => 'El tipo de inmueble no es válido.',
            
            'properties.*.valor_estimado.required' => 'El valor estimado es obligatorio.',
            'properties.*.valor_estimado.numeric' => 'El valor estimado debe ser un número.',
            
            'properties.*.imagenes.*.image' => 'El archivo debe ser una imagen válida.',
            'properties.*.imagenes.*.mimes' => 'Las imágenes deben ser: jpeg, png, jpg, gif o webp.',
            'properties.*.imagenes.*.max' => 'Cada imagen no debe superar los 2MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('valor_requerido') && is_string($this->input('valor_requerido'))) {
            $this->merge([
                'valor_requerido' => (float) str_replace([',', ' '], '', $this->input('valor_requerido'))
            ]);
        }

        if ($this->has('properties') && is_array($this->input('properties'))) {
            $properties = $this->input('properties');
            
            foreach ($properties as $index => $property) {
                if (isset($property['valor_estimado']) && is_string($property['valor_estimado'])) {
                    $properties[$index]['valor_estimado'] = (float) str_replace([',', ' '], '', $property['valor_estimado']);
                }
            }
            
            $this->merge(['properties' => $properties]);
        }
    }
}