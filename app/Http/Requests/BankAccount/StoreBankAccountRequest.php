<?php

namespace App\Http\Requests\BankAccount;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StoreBankAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // $token = PersonalAccessToken::findToken($request->bearerToken());
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
            'bank_id' => 'required|exists:banks,id',
            'type' => [
                'required',
                Rule::in(['corriente', 'ahorro']),
            ],
            'currency' => [
                'required',
                Rule::in(['PEN', 'USD']),
            ],
            'cc' => [
                'required',
                'string',
                'min:2',
                'max:15',
                'unique:bank_accounts,cc,NULL,id,investor_id,' . Auth::user()->id,
                'unique:bank_accounts,cc',
            ],
            'cci' => [
                'required',
                'string',
                'min:20',
                'max:20',
                'unique:bank_accounts,cci,NULL,id,investor_id,' . Auth::user()->id,
                'unique:bank_accounts,cci',
            ],
            'alias' => 'required|string|min:2',
        ];
    }

    /**
     * Get the proper failed validation response for the request.
     * 
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'bank_id.required' => 'El campo banco es obligatorio.',
            'bank_id.exists' => 'El banco seleccionado no existe.',
            'type.required' => 'El campo tipo es obligatorio.',
            'type.in' => 'El campo tipo debe ser corriente o ahorro.',
            'currency.required' => 'El campo moneda es obligatorio.',
            'currency.in' => 'El campo moneda debe ser PEN o USD.',
            'cc.required' => 'El campo cc es obligatorio.',
            'cc.unique' => 'El campo cc ya está registrado.',
            'cc.min' => 'El campo cc debe tener al menos 2 caracteres.',
            'cc.max' => 'El campo cc debe tener como máximo 15 caracteres.',
            'cci.required' => 'El campo cci es obligatorio.',
            'cci.unique' => 'El campo cci ya está registrado.',
            'cci.min' => 'El campo cci debe tener al menos 20 caracteres.',
            'cci.max' => 'El campo cci debe tener como máximo 20 caracteres.',
            'alias.required' => 'El campo alias es obligatorio.',
        ];
    }
}
