<?php

namespace App\Http\Requests\Investor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvestorConfirmAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_pep' => 'required|boolean',
            'has_relationship_pep' => 'required|boolean',
            'department' => 'required|regex:/^[0-9]{2}$/',
            'province' => 'required|regex:/^[0-9]{2}$/',
            'district' => 'required|regex:/^[0-9]{2}$/',
            'address' => 'required|string',
            // 🔑 allow partial updates + PDFs
            'document_front'       => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'document_back'        => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'investor_photo_path'  => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    public function messages()
    {
        return [
            'is_pep.required' => 'Debes confirmar que eres PEP.',
            'has_relationship_pep.required' => 'Debes confirmar si tienes relación con un PEP.',
            'department.required' => 'Debes indicar el departamento.',
            'province.required' => 'Debes indicar la provincia.',
            'district.required' => 'Debes indicar el distrito.',
            'address.required' => 'Debes indicar la dirección.',

            'document_front.required' => 'Debes subir la foto frontal de tu documento.',
            'document_back.required' => 'Debes subir la foto posterior de tu documento.',
            'investor_photo_path.required' => 'Debes subir una foto tuya.',

            'regex' => 'El :attribute no tiene el formato correcto.',
        ];
    }
}
