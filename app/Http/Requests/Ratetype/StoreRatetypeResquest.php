<?php

namespace App\Http\Requests\Ratetype;

use Illuminate\Foundation\Http\FormRequest;

class StoreRatetypeResquest extends FormRequest{
    public function authorize(){
        return true;
    }
    public function rules(){
        return [
            'nombre' => 'required|string|max:100|unique:rate_types,nombre',
            'descripcion' => 'nullable|string|max:255',
        ];
    }
}
