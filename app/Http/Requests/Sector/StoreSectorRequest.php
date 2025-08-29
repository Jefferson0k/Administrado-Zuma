<?php

namespace App\Http\Requests\Sector;

use Illuminate\Foundation\Http\FormRequest;

class StoreSectorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:sectors,name',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del sector es obligatorio.',
            'name.string'   => 'El nombre del sector debe ser un texto válido.',
            'name.max'      => 'El nombre del sector no puede superar los 255 caracteres.',
            'name.unique'   => 'Este sector ya está registrado.',
        ];
    }
}
