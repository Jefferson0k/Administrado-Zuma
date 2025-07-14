<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

class PropertyUpdateRequest extends FormRequest{
    public function authorize(): bool{
        return true;
    }
    public function rules(){
        return [
            'tea' => 'required|numeric|min:0|max:100',
            'tem' => 'required|numeric|min:0|max:20',
            'deadlines_id' => 'required|exists:deadlines,id',
            'riesgo' => 'required|in:A+,A,B,C,D',
            'tipo_cronograma' => 'required|in:frances,americano',
            'estado_property' => 'nullable|in:activa,desactiva',
            //'estado_property' => 'required|in:activa,desactivada',
            'estado_configuracion' => 'required|in:1,2',
        ];
    }
}
