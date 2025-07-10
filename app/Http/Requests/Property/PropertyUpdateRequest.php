<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

class PropertyUpdateRequest extends FormRequest{
    public function authorize(): bool{
        return true;
    }
    public function rules(): array{
        return [
            'tea' => 'required',
            'tem' => 'required',
            'estado' => 'required',
            'tipo_cronograma' => 'required',
            'riesgo' => 'required',
            'deadlines_id' => 'required|exists:deadlines,id',
        ];
    }
}
