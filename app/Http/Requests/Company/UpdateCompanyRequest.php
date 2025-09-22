<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza datos antes de validar.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'risk'                 => is_numeric($this->input('risk')) ? (int) $this->input('risk') : $this->input('risk'),
            'link_web_page'        => $this->filled('link_web_page') ? trim((string) $this->input('link_web_page')) : $this->input('link_web_page'),
            // ✅ nuevo campo: trim si viene
            'nuevonombreempresa'   => $this->filled('nuevonombreempresa') ? trim((string) $this->input('nuevonombreempresa')) : $this->input('nuevonombreempresa'),
        ]);
    }

    public function rules(): array
    {
        // Soporta distintos nombres de parámetro en la ruta (id | company)
        $routeId = $this->route('id')
            ?? ($this->route('company') instanceof \App\Models\Company ? $this->route('company')->id : $this->route('company'))
            ?? optional($this->user())->company_id;

        // Requeridos (los únicos que vas a editar)
        $required = [
            'risk'          => ['required', 'integer', 'between:0,4'],
            'description'   => ['required', 'string', 'min:1', 'max:250'],
            'link_web_page' => ['required', 'string', 'url', 'max:255'],
        ];

        // Opcionales (solo se validan si vienen en el payload)
        $optional = [
            'name'               => ['sometimes', 'string', 'max:255', Rule::unique('companies', 'name')->ignore($routeId)],
            'business_name'      => ['sometimes', 'string', 'max:255'],
            'sector_id'          => ['sometimes', 'integer', 'exists:sectors,id'],
            'subsector_id'       => ['sometimes', 'nullable', 'integer', 'exists:subsectors,id'],
            'incorporation_year' => ['sometimes', 'integer', 'digits:4', 'between:1800,' . date('Y')],
            'document'           => ['sometimes', 'numeric', 'digits_between:8,11', Rule::unique('companies', 'document')->ignore($routeId)],
            'moneda'             => ['sometimes', 'in:USD,PEN,BOTH'],

            // Ventas (solo requeridas si envías "moneda" y coincide)
            'sales_PEN'          => ['nullable', 'numeric', 'min:0', 'required_if:moneda,PEN,BOTH'],
            'sales_USD'          => ['nullable', 'numeric', 'min:0', 'required_if:moneda,USD,BOTH'],

            // Si envías campos financieros adicionales, se validan; si no, se ignoran
            'facturas_financiadas_pen'    => ['sometimes', 'nullable', 'integer', 'min:0'],
            'monto_total_financiado_pen'  => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'pagadas_pen'                 => ['sometimes', 'nullable', 'integer', 'min:0'],
            // 'pendientes_pen'              => ['sometimes', 'nullable', 'integer', 'min:0'],
            'plazo_promedio_pago_pen'     => ['sometimes', 'nullable', 'integer', 'min:0'],

            'facturas_financiadas_usd'    => ['sometimes', 'nullable', 'integer', 'min:0'],
            'monto_total_financiado_usd'  => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'pagadas_usd'                 => ['sometimes', 'nullable', 'integer', 'min:0'],
            // 'pendientes_usd'              => ['sometimes', 'nullable', 'integer', 'min:0'],
            'plazo_promedio_pago_usd'     => ['sometimes', 'nullable', 'integer', 'min:0'],

            // ✅ NUEVO CAMPO (solo en update)
            'nuevonombreempresa'          => ['sometimes', 'nullable', 'string', 'max:255'],
        ];

        // Acepta edición parcial tanto para PATCH como para PUT
        return $required + $optional;
    }

    public function messages(): array
    {
        return [
            'risk.required'            => 'El riesgo es obligatorio.',
            'risk.integer'             => 'El riesgo debe ser un número entero.',
            'risk.between'             => 'El riesgo debe estar entre 0 y 4.',

            'description.required'     => 'La descripción es obligatoria.',
            'description.string'       => 'La descripción debe ser texto.',
            'description.min'          => 'La descripción es demasiado corta.',
            'description.max'          => 'La descripción no puede superar los 250 caracteres.',

            'link_web_page.required'   => 'La página web es obligatoria.',
            'link_web_page.url'        => 'Ingrese una URL válida (http(s)://...).',
            'link_web_page.max'        => 'La URL no puede superar los 255 caracteres.',

            'name.unique'              => 'El nombre ya está registrado.',
            'document.unique'          => 'El documento ya está registrado.',

            'sector_id.exists'         => 'El sector seleccionado no existe.',
            'subsector_id.exists'      => 'El subsector seleccionado no existe.',

            'incorporation_year.digits'=> 'El año de constitución debe tener 4 dígitos.',
            'incorporation_year.between'=> 'El año debe estar entre 1800 y el año actual.',

            'moneda.in'                => 'La moneda debe ser USD, PEN o BOTH.',

            'sales_PEN.required_if'    => 'El volumen de ventas en PEN es obligatorio para la moneda seleccionada.',
            'sales_USD.required_if'    => 'El volumen de ventas en USD es obligatorio para la moneda seleccionada.',

            // ✅ mensajes del nuevo campo
            'nuevonombreempresa.string' => 'El “nuevo nombre de empresa” debe ser texto.',
            'nuevonombreempresa.max'    => 'El “nuevo nombre de empresa” no puede superar los 255 caracteres.',
        ];
    }
}
