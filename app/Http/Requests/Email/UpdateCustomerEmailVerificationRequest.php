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
    public function rules(): array{
        return [
            'expires' => [
                'sometimes', // Cambiar de 'required' a 'sometimes'
                'integer',
                function ($attribute, $value, $fail) {
                    if ($value && Carbon::now()->timestamp > intval($value)) {
                        $fail('El enlace de verificación ha expirado, vuelve a intentarlo.');
                    }
                }
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'expires.integer' => 'El formato del tiempo de expiración es inválido.',
        ];
    }
}
