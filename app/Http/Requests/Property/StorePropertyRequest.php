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
            'numero_solicitud' => 'required|string|unique:solicitud,numero_solicitud',
            'investor_id'     => 'required|exists:investors,id',

            // 'properties'                   => 'required|array|min:1',
            'properties.*.nombre'          => 'required|string|max:255',
            'properties.*.departamento'    => 'required|string|max:255',
            'properties.*.provincia'       => 'required|string|max:255',
            'properties.*.distrito'        => 'required|string|max:255',
            'properties.*.direccion'       => 'required|string|max:255',
            'properties.*.descripcion'     => 'nullable|string',
            
            'properties.*.valor_estimado'  => 'required|numeric|min:0',
            'properties.*.valor_subasta'   => 'nullable|numeric|min:0',
            'properties.*.valor_requerido' => 'required|numeric|min:0',
            'properties.*.currency_id'     => 'required|exists:currencies,id',
            'properties.*.pertenece'       => 'nullable|string|max:255',
            'properties.*.id_tipo_inmueble'=> 'required|exists:tipo_inmueble,id_tipo_inmueble',
            'properties.*.estado'          => 'nullable|string|in:activa,inactiva,vendida',                        
            'properties.*.imagenes'        => 'required|array|min:3',
            'properties.*.imagenes.*'      => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];
        
        // Si es UPDATE (tiene parámetro de ruta), agregar reglas para eliminación
        if ($this->route('property') || $this->isMethod('put') || $this->isMethod('patch')) {
            // Para updates, las imágenes nuevas son opcionales pero si se envían deben cumplir las reglas
            $rules['imagenes'] = 'nullable|array';
            $rules['imagenes.*'] = 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048';
            $rules['imagenes_eliminar'] = 'nullable|array';
            $rules['imagenes_eliminar.*'] = 'nullable|string|exists:property_images,imagen';
        }

        
        return $rules;
    }

    public function messages(): array
    {
        return [
            // 'properties.required' => 'La propiedad es requerida',
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
            
            'imagenes.required' => 'Las imágenes son obligatorias.',
            'imagenes.array' => 'Las imágenes deben ser un array.',
            'imagenes.min' => 'Debes subir al menos 3 imágenes.',
            'imagenes.*.required' => 'Cada imagen es obligatoria.',
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
            'id_tipo:inmueble' => 'nombre de la propiedad',
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
        
        if ($this->has('properties')) {
        $properties = $this->input('properties');
        foreach ($properties as $key => $prop) {
            foreach (['valor_estimado', 'valor_subasta', 'valor_requerido'] as $field) {
                if (isset($prop[$field])) {
                    $cleanValue = str_replace([',', ' '], '', $prop[$field]);
                    $properties[$key][$field] = (float) $cleanValue;
                }
            }
        }
        $this->merge(['properties' => $properties]);
    }
    }
}