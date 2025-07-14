<?php

namespace App\Http\Requests\Ratetype;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRatetypeResquest extends FormRequest{
    public function authorize(){
        return true;
    }
    public function rules(){
        return [
            'nombre' => 'required|string|max:100|unique:rate_types,nombre,' . $this->route('rate_type')->id,
            'descripcion' => 'nullable|string|max:255',
        ];
    }
}
