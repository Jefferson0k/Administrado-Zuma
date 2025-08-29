<?php

namespace App\Http\Requests\Investor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvestorConfirmAccountRequest extends FormRequest
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
            'is_pep' => 'required|boolean',
            'has_relationship_pep' => 'required|boolean',
            'department' => 'required|regex:/^[0-9]{2}$/',
            'province' => 'required|regex:/^[0-9]{2}$/',
            'district' => 'required|regex:/^[0-9]{2}$/',
            'address' => 'required|string',
            // is image valid? max 5mb
            'document_front' => 'required|image|mimes:jpeg,png,jpg,svg|max:5000',
            'document_back' => 'required|image|mimes:jpeg,png,jpg,svg|max:5000',
        ];
    }

    public function messages()
    {
        return [
            'is_pep.required' => 'Debes confirmar que eres PEP.',
            'has_relationship_pep.required' => 'Debes confirmar que eres PEP con relación.',
            'department.required' => 'Debes indicar el departamento.',
            'province.required' => 'Debes indicar el provincia.',
            'district.required' => 'Debes indicar el distrito.',
            'address.required' => 'Debes indicar la dirección.',
            'document_front.required' => 'Debes subir una foto de tu documento.',
            'document_back.required' => 'Debes subir una foto de tu documento.',
            'regex' => 'El :attribute no tiene el formato correcto.',
        ];
    }
}
