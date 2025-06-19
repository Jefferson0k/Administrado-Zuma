<?php

namespace App\Http\Requests\Email;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerEmailVerificationRequest extends FormRequest
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
            'expires' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    if (Carbon::now()->timestamp > intval($value)) {
                        $fail('EL enlace de verificación ha expirado, vuelve a intentarlo.');
                    }
                }
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'expires.required' => 'Falta el tiempo de expiración del enlace de verificación.',
        ];
    }
}
