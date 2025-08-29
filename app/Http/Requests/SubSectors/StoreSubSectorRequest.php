<?php

namespace App\Http\Requests\SubSectors;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubSectorRequest extends FormRequest{
    public function authorize(): bool{
        return true;
    }

    public function rules(): array{
        return [
            'name' => 'required|string|max:255|unique:subsectors,name',
            'sector_id' => 'required|exists:sectors,id',
        ];
    }

    public function messages(): array{
        return [
            'name.required'   => 'El nombre del subsector es obligatorio.',
            'name.string'     => 'El nombre del subsector debe ser un texto válido.',
            'name.max'        => 'El nombre del subsector no puede superar los 255 caracteres.',
            'name.unique'     => 'Ya existe un subsector con este nombre.',

            'sector_id.required' => 'Debe seleccionar un sector.',
            'sector_id.exists'   => 'El sector seleccionado no es válido.',
        ];
    }
}
