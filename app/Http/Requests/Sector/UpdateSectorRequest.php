<?php

namespace App\Http\Requests\Sector;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSectorRequest extends FormRequest{
    public function authorize(): bool{
        return true;
    }
    public function rules(): array{
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sectors', 'name')->ignore($this->route('sector')),
            ],
        ];
    }
    public function messages(): array{
        return [
            'name.required' => 'El nombre del sector es obligatorio.',
            'name.string'   => 'El nombre del sector debe ser un texto válido.',
            'name.max'      => 'El nombre del sector no puede superar los 255 caracteres.',
            'name.unique'   => 'Este sector ya existe.',
        ];
    }
}
