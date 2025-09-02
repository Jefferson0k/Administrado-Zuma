<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'               => 'required|string|max:255|unique:companies,name,' . $this->route('id'),
            'risk'               => 'required|numeric|min:0|max:5',
            'business_name'      => 'required|string|max:255',
            'sector_id'          => 'required|exists:sectors,id',
            'subsector_id'       => 'required|exists:subsectors,id',
            'incorporation_year' => 'required|integer|between:1800,' . date('Y') . '|digits:4',

            'sales_PEN' => [
                'numeric',
                'min:0',
                'required_if:moneda,PEN',
                'required_if:moneda,BOTH',
            ],
            'sales_USD' => [
                'numeric',
                'min:0',
                'required_if:moneda,USD',
                'required_if:moneda,BOTH',
            ],

            'document'           => 'required|numeric|digits_between:8,11|unique:companies,document,' . $this->route('id'),
            'link_web_page'      => 'required|url',
            'description'        => 'required|string',
            'moneda'             => 'required|in:USD,PEN,BOTH',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'              => 'El nombre es obligatorio.',
            'name.string'                => 'El nombre debe ser un texto.',
            'name.max'                   => 'El nombre no puede superar los 255 caracteres.',
            'name.unique'                => 'El nombre ya está registrado.',

            'risk.required'              => 'El riesgo es obligatorio.',
            'risk.numeric'               => 'El riesgo debe ser un número.',
            'risk.min'                   => 'El riesgo no puede ser menor a 0.',
            'risk.max'                   => 'El riesgo no puede ser mayor a 5.',

            'business_name.required'     => 'La razón social es obligatoria.',
            'business_name.string'       => 'La razón social debe ser un texto.',
            'business_name.max'          => 'La razón social no puede superar los 255 caracteres.',

            'sector_id.required'         => 'El sector es obligatorio.',
            'sector_id.exists'           => 'El sector seleccionado no existe.',

            'subsector_id.required'      => 'El subsector es obligatorio.',
            'subsector_id.exists'        => 'El subsector seleccionado no existe.',

            'incorporation_year.required'=> 'El año de constitución es obligatorio.',
            'incorporation_year.digits'  => 'El año de constitución debe tener 4 dígitos.',
            'incorporation_year.min'     => 'El año de constitución no puede ser menor a 1800.',
            'incorporation_year.max'     => 'El año de constitución no puede ser mayor al año actual.',

            'sales_PEN.required_if'      => 'El volumen de ventas en PEN es obligatorio según la moneda seleccionada.',
            'sales_PEN.numeric'          => 'El volumen de ventas en PEN debe ser numérico.',
            'sales_PEN.min'              => 'El volumen de ventas en PEN no puede ser negativo.',

            'sales_USD.required_if'      => 'El volumen de ventas en USD es obligatorio según la moneda seleccionada.',
            'sales_USD.numeric'          => 'El volumen de ventas en USD debe ser numérico.',
            'sales_USD.min'              => 'El volumen de ventas en USD no puede ser negativo.',

            'document.required'          => 'El documento es obligatorio.',
            'document.numeric'           => 'El documento debe ser numérico.',
            'document.digits_between'    => 'El documento debe tener entre 8 y 11 dígitos.',
            'document.unique'            => 'El documento ya está registrado.',

            'link_web_page.required'     => 'El enlace de la página web es obligatorio.',
            'link_web_page.url'          => 'El enlace debe ser una URL válida.',

            'description.required'       => 'La descripción es obligatoria.',
            'description.string'         => 'La descripción debe ser texto.',

            'moneda.required'            => 'La moneda es obligatoria.',
            'moneda.in'                  => 'La moneda debe ser USD, PEN o BOTH.',
        ];
    }
}
