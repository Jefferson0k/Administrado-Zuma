<?php

namespace App\Http\Requests\Investor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvestorAvatarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,svg|max:5000',
            'avatar_type' => 'required|integer|min:1|max:6',
            'clothing_color' => 'required|string|max:20',
            'background_color' => 'required|string|max:20',
            'medal' => 'nullable|integer|min:1|max:4',
            'medal_position' => 'nullable|json',
            'hat' => 'nullable|integer|min:1|max:6',
            'hat_position' => 'nullable|json',
            'trophy' => 'nullable|integer|min:1|max:4',
            'other' => 'nullable|integer|min:1|max:5',
        ];
    }

    public function messages()
    {
        return [
            'avatar.required'           => 'Debes subir una foto.',
            'avatar.mimes'              => 'El :attribute no tiene el formato correcto.',
            'avatar.max'                => 'El :attribute no debe pesar más de 5MB.',
            'avatar_type.required'      => 'Debes seleccionar un tipo de avatar.',
            'avatar_type.integer'       => 'El tipo de avatar debe ser un número.',
            'avatar_type.min' => 'El tipo de avatar debe ser un número entre 1 y 6.',
            'avatar_type.max' => 'El tipo de avatar debe ser un número entre 1 y 6.',
            'clothing_color.required' => 'Debes seleccionar un color de ropa.',
            'clothing_color.string' => 'El color de ropa debe ser una cadena de texto.',
            'clothing_color.max' => 'El color de ropa no debe exceder 20 caracteres.',
            'background_color.required' => 'Debes seleccionar un color de fondo.',
            'background_color.string' => 'El color de fondo debe ser una cadena de texto.',
            'background_color.max' => 'El color de fondo no debe exceder 20 caracteres.',
            'medal.integer' => 'La medalla debe ser un número.',
            'medal.min' => 'La medalla debe ser un número entre 1 y 4.',
            'medal.max' => 'La medalla debe ser un número entre 1 y 4.',
            'medal_position.json' => 'La posición de la medalla debe ser un JSON válido.',
            'hat.integer' => 'El sombrero debe ser un número.',
            'hat.min' => 'El sombrero debe ser un número entre 1 y 6.',
            'hat.max' => 'El sombrero debe ser un número entre 1 y 6.',
            'hat_position.json' => 'La posición del sombrero debe ser un JSON válido.',
            'trophy.integer' => 'El trofeo debe ser un número.',
            'trophy.min' => 'El trofeo debe ser un número entre 1 y 4.',
            'trophy.max' => 'El trofeo debe ser un número entre 1 y 4.',
            'other.integer' => 'La opción debe ser un número.',
            'other.min' => 'La opción debe ser un número entre 1 y 5.',
            'other.max' => 'La opción debe ser un número entre 1 y 5.',
        ];
    }
}
