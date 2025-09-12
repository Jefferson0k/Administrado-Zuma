<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class CategoryStoreRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        return [
            'post_id'    => 'nullable',
            'product_id' => 'required',
            'nombre'     => 'required|string|max:255',
        ];
    }

    /**
     * Mensajes personalizados (opcional).
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'El producto es obligatorio.',
            //'product_id.exists'   => 'El producto seleccionado no es válido.',
            'nombre.required'     => 'El nombre de la categoría es obligatorio.',
            'nombre.max'          => 'El nombre no puede superar los 255 caracteres.',
        ];
    }
}
