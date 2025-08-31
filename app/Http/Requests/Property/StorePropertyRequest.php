<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        $rules = [
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
            'investor_id'     => 'required|exists:investors,id',
            'estado'          => 'nullable|string|in:activa,inactiva,vendida',
            
            'imagenes'        => 'nullable|array',
            'imagenes.*'      => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];
        
        // Si es UPDATE (tiene parámetro de ruta), agregar reglas para eliminación
        if ($this->route('property') || $this->isMethod('put') || $this->isMethod('patch')) {
            $rules['imagenes_eliminar'] = 'nullable|array';
            $rules['imagenes_eliminar.*'] = 'nullable|string|exists:property_images,imagen';
        }

        
        return $rules;
    }

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
            'valor_requerido.min' => 'El valor requerido debe ser mayor or igual a 0.',
            
            'currency_id.required' => 'La moneda es obligatoria.',
            'currency_id.exists' => 'La moneda seleccionada no es válida.',
            
            'investor_id.required' => 'El inversionista es obligatorio.',
            'investor_id.exists' => 'El inversionista seleccionado no es válido.',
            
            'imagenes.array' => 'Las imágenes deben ser un array.',
            'imagenes.*.image' => 'Cada archivo debe ser una imagen válida.',
            'imagenes.*.mimes' => 'Las imágenes deben ser de tipo: jpeg, png, jpg, gif o webp.',
            'imagenes.*.max' => 'Cada imagen no debe superar los 2MB.',
            
            // Mensajes para eliminación de imágenes
            'imagenes_eliminar.array' => 'Las imágenes a eliminar deben ser un array.',
            'imagenes_eliminar.*.exists' => 'Una de las imágenes seleccionadas para eliminar no existe.',
        ];
    }

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
            'imagenes_eliminar' => 'imágenes a eliminar',
        ];
    }

    /**
     * Prepare the data for validation.
     * Para CREATE: Solo limpiar (los mutators convierten)
     * Para UPDATE: Solo limpiar (el frontend ya envía en centavos)
     */
    protected function prepareForValidation(): void
    {
        // Solo limpiar strings, sin conversiones de unidades
        // Los mutators se encargan de la conversión para CREATE
        // El frontend se encarga de la conversión para UPDATE
        
        foreach (['valor_estimado', 'valor_subasta', 'valor_requerido'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $cleanValue = str_replace([',', ' '], '', $this->input($field));
                $this->merge([$field => (float) $cleanValue]);
            }
        }
    }
}