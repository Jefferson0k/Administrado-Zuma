<?php

namespace App\Http\Requests\Investor;

use App\Models\Investor;
use App\Rules\LoginValidatePassword;
use App\Rules\ValidateEmailVerified;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginInvestorRequest extends FormRequest{
    public function authorize(): bool{
        return true;
    }
    public function rules(): array
    {
        return [
            'document' => [
                'required',
                'numeric',
                'digits:8',
                'exists:investors,document',
                new ValidateEmailVerified($this->document),
            ],
            'password' => [
                'required',
                'string',
                new LoginValidatePassword($this->document),
            ],
        ];
    }
    public function messages(): array{
        return [
            'document.required' => 'Documento es obligatorio.',
            'document.numeric' => 'Documento debe ser numérico.',
            'document.digits' => 'Documento debe tener 8 dígitos.',
            'document.exists' => 'Este documento no se encuentra registrado.',
            'password.required' => 'Contraseña es obligatoria.',
            'required' => 'El :attribute es obligatorio.',
            'min' => 'El :attribute debe tener al menos :min caracteres.',
            'max' => 'El :attribute no puede tener más de :max caracteres.',
            'numeric' => 'El :attribute debe ser numérico.',
            'string' => 'El :attribute debe ser una cadena de caracteres.',
        ];
    }
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator){
        $errors = $validator->errors();
        if ($errors->has('document')) {
            $investor = Investor::where('document', $this->document)->first();
            if ($investor && !$investor->hasVerifiedEmail()) {
                $response = response()->json([
                    'success' => false,
                    'message' => 'Tu cuenta no ha sido verificada. Revisa tu correo electrónico y haz clic en el enlace de verificación.',
                    'error_type' => 'email_not_verified',
                    'user_id' => $investor->id,
                    'user_email' => $investor->email,
                    'errors' => $errors
                ], 403);
                
                throw new HttpResponseException($response);
            }
        }
        parent::failedValidation($validator);
    }
}
