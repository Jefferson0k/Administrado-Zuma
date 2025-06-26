<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

class PropertyUpdateRequest extends FormRequest{
    public function authorize(): bool{
        return true;
    }
    public function rules(): array {
        return [
            'dia_subasta' => 'required|date_format:Y-m-d',
            'hora_inicio' => 'required|date_format:H:i:s',
            'hora_fin' => 'required|date_format:H:i:s',
            'deadlines_id' => 'required|exists:deadlines,id',
        ];
    }

    public function messages(): array {
        return [
            'dia_subasta.required' => 'El día de subasta es obligatorio.',
            'hora_inicio.required' => 'La hora de inicio es obligatoria.',
            'hora_fin.required' => 'La hora de fin es obligatoria.',
            'hora_inicio.date_format' => 'La hora de inicio debe tener el formato HH:mm:ss.',
            'hora_fin.date_format' => 'La hora de fin debe tener el formato HH:mm:ss.',
            'deadlines_id.required' => 'El plazo es obligatorio.',
            'deadlines_id.exists' => 'El plazo seleccionado no existe.',
        ];
    }

}
