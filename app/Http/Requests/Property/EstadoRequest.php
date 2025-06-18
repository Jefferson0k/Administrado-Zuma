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
            #'dia_subasta' => 'required_if:estado,en_subasta|date',
            #'hora_inicio' => 'required_if:estado,en_subasta|date_format:H:i:s',
            #'hora_fin' => 'required_if:estado,en_subasta|date_format:H:i:s',
            #'monto_inicial' => 'required_if:estado,en_subasta|numeric|min:0',
        ];
    }
    public function messages(){
        return [
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser "en_subasta" o "no_subastada".',

            #'dia_subasta.required_if' => 'Debe especificar el día de la subasta.',
            #'dia_subasta.date' => 'El día de la subasta debe ser una fecha válida.',

            #'hora_inicio.required_if' => 'Debe especificar la hora de inicio.',
            #'hora_inicio.date_format' => 'La hora de inicio debe tener el formato HH:MM:SS.',

            #'hora_fin.required_if' => 'Debe especificar la hora de fin.',
            #'hora_fin.date_format' => 'La hora de fin debe tener el formato HH:MM:SS.',

            #'monto_inicial.required_if' => 'Debe especificar el monto inicial.',
            #'monto_inicial.numeric' => 'El monto inicial debe ser un número.',
            #'monto_inicial.min' => 'El monto inicial no puede ser negativo.',
        ];
    }
}
