<?php 

namespace App\Http\Requests\Property;
use Illuminate\Foundation\Http\FormRequest;

class EstadoRequest extends FormRequest{
    public function authorize(){
        return true;
    }
    public function rules(){
        return [
            'estado' => 'required|in:en_subasta,no_subastada',
        ];
    }
    public function messages(){
        return [
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser "en_subasta" o "no_subastada".',
        ];
    }
}
