<?php

namespace App\Http\Requests\BankAccount;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBankAccountRequest extends FormRequest
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
            // 'bank' => 'required|string|min:2',
            // 'type' => [
            //     'required',
            //     Rule::in(['corriente', 'ahorro']),
            // ],
            // 'currency' => [
            //     'required',
            //     Rule::in(['PEN', 'USD']),
            // ],
            // 'cc' => 'required|string|min:2',
            // 'cci' => 'required|string|min:2',
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
            'bank.required' => 'El campo banco es obligatorio.',
            'type.required' => 'El campo tipo es obligatorio.',
            'type.in' => 'El campo tipo debe ser corriente o ahorro.',
            'currency.required' => 'El campo moneda es obligatorio.',
            'currency.in' => 'El campo moneda debe ser PEN o USD.',
            'cc.required' => 'El campo cc es obligatorio.',
            'cci.required' => 'El campo cci es obligatorio.',
            'alias.required' => 'El campo alias es obligatorio.',
        ];
    }
}
