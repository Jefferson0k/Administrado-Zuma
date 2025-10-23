<?php

namespace App\Http\Requests\Property;

use App\Models\Investor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            // Campos para Solicitud
            'investor_id' => 'required|exists:investors,id',
            'codigo' => 'required|string|max:255',
            'valor_general' => 'required|numeric|min:0',
            'valor_requerido' => 'required|numeric|min:0',
            'currency_id' => 'required|exists:currencies,id',

            // Estos tres campos serán condicionales según el tipo de inversor
            'fuente_ingreso' => 'nullable|string|max:255',
            'profesion_ocupacion' => 'nullable|string|max:255',
            'ingreso_promedio' => 'nullable|numeric|min:0',

            // Array de propiedades
            'properties' => 'required|array|min:1',
            'properties.*.nombre' => 'required|string|max:255',
            'properties.*.departamento' => 'required|string|max:255',
            'properties.*.provincia' => 'required|string|max:255',
            'properties.*.distrito' => 'required|string|max:255',
            'properties.*.direccion' => 'required|string|max:255',
            'properties.*.descripcion' => 'nullable|string',
            'properties.*.pertenece' => 'nullable|string|max:255',
            'properties.*.id_tipo_inmueble' => 'required|exists:tipo_inmueble,id_tipo_inmueble',
            'properties.*.valor_estimado' => 'required|numeric|min:0',

            // Campos de ubicación
            'properties.*.departamento_id' => 'required|string|max:10',
            'properties.*.provincia_id' => 'required|string|max:10',
            'properties.*.distrito_id' => 'required|string|max:10',

            // Imágenes
            'properties.*.imagenes' => 'required|array|min:3',
            'properties.*.imagenes.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp',
            'properties.*.description' => 'required|array',
            'properties.*.description.*' => 'required|string|max:255',
        ];

        // Si es UPDATE, ajustar las reglas
        if ($this->route('property') || $this->isMethod('put') || $this->isMethod('patch')) {
            $rules['properties.*.imagenes'] = 'nullable|array';
            $rules['properties.*.imagenes.*'] = 'nullable|image|mimes:jpeg,png,jpg,gif,webp';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'investor_id.required' => 'El inversionista es obligatorio.',
            'investor_id.exists' => 'El inversionista seleccionado no es válido.',
            'codigo.required' => 'El código de solicitud es obligatorio.',
            'valor_general.required' => 'El valor general es obligatorio.',
            'valor_general.numeric' => 'El valor general debe ser un número.',
            'valor_requerido.required' => 'El valor requerido es obligatorio.',
            'valor_requerido.numeric' => 'El valor requerido debe ser un número.',
            'currency_id.required' => 'La moneda es obligatoria.',
            'currency_id.exists' => 'La moneda seleccionada no es válida.',

            'properties.required' => 'Debe haber al menos una propiedad.',
            'properties.array' => 'Las propiedades deben ser un array.',
            'properties.min' => 'Debe haber al menos una propiedad.',

            'properties.*.nombre.required' => 'El nombre de la propiedad es obligatorio.',
            'properties.*.departamento.required' => 'El departamento es obligatorio.',
            'properties.*.provincia.required' => 'La provincia es obligatoria.',
            'properties.*.distrito.required' => 'El distrito es obligatorio.',
            'properties.*.direccion.required' => 'La dirección es obligatoria.',
            'properties.*.id_tipo_inmueble.required' => 'El tipo de inmueble es obligatorio.',
            'properties.*.id_tipo_inmueble.exists' => 'El tipo de inmueble seleccionado no es válido.',
            'properties.*.valor_estimado.required' => 'El valor estimado es obligatorio.',
            'properties.*.valor_estimado.numeric' => 'El valor estimado debe ser un número.',
            'properties.*.valor_estimado.min' => 'El valor estimado debe ser mayor o igual a 0.',

            'properties.*.departamento_id.required' => 'El ID de departamento es obligatorio.',
            'properties.*.provincia_id.required' => 'El ID de provincia es obligatorio.',
            'properties.*.distrito_id.required' => 'El ID de distrito es obligatorio.',

            'properties.*.imagenes.required' => 'Las imágenes son obligatorias.',
            'properties.*.imagenes.array' => 'Las imágenes deben ser un array.',
            'properties.*.imagenes.min' => 'Debes subir al menos 3 imágenes por propiedad.',
            'properties.*.imagenes.*.image' => 'Cada archivo debe ser una imagen válida.',
            'properties.*.imagenes.*.mimes' => 'Las imágenes deben ser de tipo: jpeg, png, jpg, gif o webp.',
            'properties.*.imagenes.*.max' => 'Cada imagen no debe superar los 2MB.',

            'properties.*.description.required' => 'Las descripciones de imágenes son obligatorias.',
            'properties.*.description.array' => 'Las descripciones deben ser un array.',
            'properties.*.description.*.required' => 'Cada descripción de imagen es obligatoria.',
            'properties.*.description.*.string' => 'Cada descripción debe ser texto.',
            'properties.*.description.*.max' => 'Cada descripción no debe superar los 255 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'investor_id' => 'inversionista',
            'codigo' => 'código de solicitud',
            'valor_general' => 'valor general',
            'valor_requerido' => 'valor requerido',
            'currency_id' => 'moneda',
            'fuente_ingreso' => 'fuente de ingreso',
            'profesion_ocupacion' => 'profesión u ocupación',
            'ingreso_promedio' => 'ingreso promedio',
            'properties' => 'propiedades',
            'properties.*.nombre' => 'nombre de la propiedad',
            'properties.*.departamento' => 'departamento',
            'properties.*.provincia' => 'provincia',
            'properties.*.distrito' => 'distrito',
            'properties.*.direccion' => 'dirección',
            'properties.*.descripcion' => 'descripción',
            'properties.*.pertenece' => 'pertenece a',
            'properties.*.id_tipo_inmueble' => 'tipo de inmueble',
            'properties.*.valor_estimado' => 'valor estimado',
            'properties.*.departamento_id' => 'ID departamento',
            'properties.*.provincia_id' => 'ID provincia',
            'properties.*.distrito_id' => 'ID distrito',
            'properties.*.imagenes' => 'imágenes',
            'properties.*.description' => 'descripciones de imágenes',
        ];
    }

    /**
     * Valida dinámicamente según el tipo de inversionista.
     */
    public function withValidator($validator)
    {
        $validator->after(function (Validator $v) {
            $investorId = $this->input('investor_id');
            if (!$investorId) return;

            $investor = Investor::find($investorId);

            // Si el inversionista NO es cliente o mixto, estos campos deben ser obligatorios
            if ($investor && !in_array($investor->type, ['cliente', 'mixto'])) {
                foreach (['fuente_ingreso', 'profesion_ocupacion', 'ingreso_promedio'] as $field) {
                    if (!$this->filled($field)) {
                        $v->errors()->add($field, 'Este campo es obligatorio para inversionistas nuevos.');
                    }
                }
            }
        });
    }

    /**
     * Limpieza de valores antes de validar.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('valor_general') && is_string($this->input('valor_general'))) {
            $this->merge(['valor_general' => (float) str_replace([',', ' '], '', $this->input('valor_general'))]);
        }

        if ($this->has('valor_requerido') && is_string($this->input('valor_requerido'))) {
            $this->merge(['valor_requerido' => (float) str_replace([',', ' '], '', $this->input('valor_requerido'))]);
        }

        if ($this->has('properties') && is_array($this->input('properties'))) {
            $properties = $this->input('properties');

            foreach ($properties as $index => $property) {
                if (isset($property['valor_estimado']) && is_string($property['valor_estimado'])) {
                    $properties[$index]['valor_estimado'] = (float) str_replace([',', ' '], '', $property['valor_estimado']);
                }

                if (isset($property['valor_requerido']) && is_string($property['valor_requerido'])) {
                    $properties[$index]['valor_requerido'] = (float) str_replace([',', ' '], '', $property['valor_requerido']);
                }
            }

            $this->merge(['properties' => $properties]);
        }
    }
}
