<?php

namespace App\Http\Requests\Bid;

use Illuminate\Foundation\Http\FormRequest;

class FiltradoRequest extends FormRequest{
    public function authorize(): bool{
        return true;
    }
    public function rules(): array{
        return [
            'auction_id' => 'required|integer|exists:auctions,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
    public function messages(): array{
        return [
            'auction_id.required' => 'El ID de la subasta es obligatorio.',
            'auction_id.integer' => 'El ID de la subasta debe ser un número entero.',
            'auction_id.exists' => 'La subasta especificada no existe.',
            'per_page.integer' => 'El número de elementos por página debe ser un número entero.',
            'per_page.min' => 'Debe mostrar al menos 1 elemento por página.',
            'per_page.max' => 'No se pueden mostrar más de 100 elementos por página.',
        ];
    }
}