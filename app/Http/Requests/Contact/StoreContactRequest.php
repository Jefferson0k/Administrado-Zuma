<?php

namespace App\Http\Requests\Contact;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class StoreContactRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'interested_product' => 'required|string|in:Facturas,Prestamos,tasas',
            'message' => 'nullable|string|max:1000',
            'accepted_policy' => 'required|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'El nombre completo es obligatorio.',
            'full_name.string' => 'El nombre debe ser texto válido.',
            'full_name.max' => 'El nombre no puede exceder 255 caracteres.',
            
            'phone.required' => 'El teléfono es obligatorio.',
            'phone.string' => 'El teléfono debe ser texto válido.',
            'phone.max' => 'El teléfono no puede exceder 20 caracteres.',
            
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.max' => 'El correo no puede exceder 255 caracteres.',
            
            'interested_product.required' => 'Debes seleccionar un producto de interés.',
            'interested_product.in' => 'El producto seleccionado no es válido.',
            
            'message.string' => 'El mensaje debe ser texto válido.',
            'message.max' => 'El mensaje no puede exceder 1000 caracteres.',
            
            'accepted_policy.required' => 'Debes aceptar la política de privacidad.',
            'accepted_policy.boolean' => 'La aceptación de política debe ser verdadero o falso.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'full_name' => 'nombre completo',
            'phone' => 'teléfono',
            'email' => 'correo electrónico',
            'interested_product' => 'producto de interés',
            'message' => 'mensaje',
            'accepted_policy' => 'política de privacidad',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Los datos proporcionados no son válidos.',
                'success' => false,
                'errors' => $validator->errors(),
                'required_fields' => $this->getRequiredFields(),
                'field_details' => $this->getFieldDetails()
            ], 422)
        );
    }

    /**
     * Get list of required fields
     */
    private function getRequiredFields(): array
    {
        $rules = $this->rules();
        $required = [];
        
        foreach ($rules as $field => $rule) {
            if (is_string($rule) && str_contains($rule, 'required')) {
                $required[] = $field;
            } elseif (is_array($rule) && in_array('required', $rule)) {
                $required[] = $field;
            }
        }
        
        return $required;
    }

    /**
     * Get detailed field information
     */
    private function getFieldDetails(): array
    {
        return [
            'full_name' => [
                'required' => true,
                'type' => 'string',
                'max_length' => 255,
                'label' => 'Nombre completo'
            ],
            'phone' => [
                'required' => true,
                'type' => 'string',
                'max_length' => 20,
                'label' => 'Teléfono'
            ],
            'email' => [
                'required' => true,
                'type' => 'email',
                'max_length' => 255,
                'label' => 'Correo electrónico'
            ],
            'interested_product' => [
                'required' => true,
                'type' => 'select',
                'options' => ['Facturas', 'Prestamos', 'tasas'],
                'label' => 'Producto de interés'
            ],
            'message' => [
                'required' => false,
                'type' => 'text',
                'max_length' => 1000,
                'label' => 'Mensaje'
            ],
            'accepted_policy' => [
                'required' => true,
                'type' => 'boolean',
                'label' => 'Aceptar política de privacidad'
            ]
        ];
    }
}