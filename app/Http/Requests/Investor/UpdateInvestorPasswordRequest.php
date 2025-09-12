<?php

namespace App\Http\Requests\Investor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvestorPasswordRequest extends FormRequest
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
            'password' => 'required|string|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
            'confirm_password' => [
                'required',
                'string',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
                function ($attribute, $value, $fail) {
                    if ($value != $this->password) {
                        $fail('Confirmar contraseña no coincide con la contraseña.');
                    }
                },
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
            'password.required' => 'Contraseña es obligatoria.',
            'password.regex' => 'La Contraseña  debe tener al menos 8 caracteres, una mayúscula, un número y un carácter especial.',
            'confirm_password.required' => 'Confirmar contraseña es obligatoria.',
            'confirm_password.regex' => 'La confirmación de la contraseña  debe tener al menos 8 caracteres, una mayúscula, un número y un carácter especial.',
        ];
    }
}
