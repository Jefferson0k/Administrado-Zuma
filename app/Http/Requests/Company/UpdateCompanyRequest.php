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
        $companyId = $this->route('company') ?? $this->route('id');

        return [
            'name'               => 'required|string|max:255|unique:companies,name,' . $companyId,
            'risk'               => 'required|string|max:255',
            'business_name'      => 'required|string|max:255',
            'sector_id'          => 'required|exists:sectors,id',
            'subsector_id'       => 'required|exists:subsectors,id',
            'incorporation_year' => 'required|digits:4|integer|min:1800|max:' . date('Y'),
            'sales_volume'       => 'required|numeric|min:0',
            'document'           => 'required|string|regex:/^[0-9]{8,11}$/|unique:companies,document,' . $companyId,
            'link_web_page'      => 'required|url',
            'description'        => 'required|string',
            'moneda'             => 'required|in:USD,PEN,BOTH',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'             => 'El nombre es obligatorio.',
            'name.string'               => 'El nombre debe ser un texto.',
            'name.max'                  => 'El nombre no puede superar los 255 caracteres.',
            'name.unique'               => 'El nombre ya está registrado.',

            'risk.required'             => 'El riesgo es obligatorio.',
            'risk.string'               => 'El riesgo debe ser un texto.',

            'business_name.required'    => 'La razón social es obligatoria.',
            'business_name.string'      => 'La razón social debe ser un texto.',
            'business_name.max'         => 'La razón social no puede superar los 255 caracteres.',

            'sector_id.required'        => 'El sector es obligatorio.',
            'sector_id.exists'          => 'El sector seleccionado no existe.',

            'subsector_id.required'     => 'El subsector es obligatorio.',
            'subsector_id.exists'       => 'El subsector seleccionado no existe.',

            'incorporation_year.required'=> 'El año de constitución es obligatorio.',
            'incorporation_year.digits' => 'El año de incorporación debe tener 4 dígitos.',
            'incorporation_year.integer'=> 'El año de incorporación debe ser un número.',
            'incorporation_year.min'    => 'El año de incorporación no puede ser menor a 1800.',
            'incorporation_year.max'    => 'El año de incorporación no puede ser mayor al año actual.',

            'sales_volume.required'     => 'El volumen de ventas es obligatorio.',
            'sales_volume.numeric'      => 'El volumen de ventas debe ser numérico.',
            'sales_volume.min'          => 'El volumen de ventas no puede ser negativo.',

            'document.required'         => 'El documento es obligatorio.',
            'document.regex'            => 'El documento debe tener entre 8 y 11 dígitos numéricos.',
            'document.unique'           => 'El documento ya está registrado.',

            'link_web_page.required'    => 'El enlace de la página web es obligatorio.',
            'link_web_page.url'         => 'El enlace debe ser una URL válida.',

            'description.required'      => 'La descripción es obligatoria.',
            'description.string'        => 'La descripción debe ser texto.',

            'moneda.required'           => 'La moneda es obligatoria.',
            'moneda.in'                 => 'La moneda debe ser USD, PEN o BOTH.',
        ];
    }
}
