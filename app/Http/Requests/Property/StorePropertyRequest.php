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
            'investor_id'     => 'required|exists:investors,id', // Fix: Added proper validation
            'estado'          => 'nullable|string|in:activa,inactiva,vendida', // Fix: Added estado validation if needed
            
            'imagenes'        => 'nullable|array',
            'imagenes.*'      => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // Fix: Added specific mime types
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la propiedad es obligatorio.',
            'departamento.required' => 'El departamento es obligatorio.',
            'provincia.required' => 'La provincia es obligatoria.',
            'distrito.required' => 'El distrito es obligatorio.',
            'direccion.required' => 'La dirección es obligatoria.',
            
            'valor_estimado.required' => 'El valor estimado es obligatorio.',
            'valor_estimado.numeric' => 'El valor estimado debe ser un número.',
            'valor_estimado.min' => 'El valor estimado debe ser mayor o igual a 0.',
            
            'valor_subasta.numeric' => 'El valor de subasta debe ser un número.',
            'valor_subasta.min' => 'El valor de subasta debe ser mayor o igual a 0.',
            
            'valor_requerido.required' => 'El valor requerido es obligatorio.',
            'valor_requerido.numeric' => 'El valor requerido debe ser un número.',
            'valor_requerido.min' => 'El valor requerido debe ser mayor o igual a 0.',
            
            'currency_id.required' => 'La moneda es obligatoria.',
            'currency_id.exists' => 'La moneda seleccionada no es válida.',
            
            'investor_id.required' => 'El inversionista es obligatorio.',
            'investor_id.exists' => 'El inversionista seleccionado no es válido.',
            
            'imagenes.array' => 'Las imágenes deben ser un array.',
            'imagenes.*.image' => 'Cada archivo debe ser una imagen válida.',
            'imagenes.*.mimes' => 'Las imágenes deben ser de tipo: jpeg, png, jpg, gif o webp.',
            'imagenes.*.max' => 'Cada imagen no debe superar los 2MB.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre de la propiedad',
            'departamento' => 'departamento',
            'provincia' => 'provincia',
            'distrito' => 'distrito',
            'direccion' => 'dirección',
            'descripcion' => 'descripción',
            'valor_estimado' => 'valor estimado',
            'valor_subasta' => 'valor de subasta',
            'valor_requerido' => 'valor requerido',
            'currency_id' => 'moneda',
            'investor_id' => 'inversionista',
            'imagenes' => 'imágenes',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string numbers to actual numbers if they come as strings
        if ($this->has('valor_estimado') && is_string($this->valor_estimado)) {
            $this->merge([
                'valor_estimado' => (float) str_replace(',', '', $this->valor_estimado)
            ]);
        }

        if ($this->has('valor_subasta') && is_string($this->valor_subasta)) {
            $this->merge([
                'valor_subasta' => (float) str_replace(',', '', $this->valor_subasta)
            ]);
        }

        if ($this->has('valor_requerido') && is_string($this->valor_requerido)) {
            $this->merge([
                'valor_requerido' => (float) str_replace(',', '', $this->valor_requerido)
            ]);
        }
    }
}