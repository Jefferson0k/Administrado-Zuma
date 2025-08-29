<?php

namespace App\Http\Requests\Withdraw;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Helpers\MoneyConverter;

use function PHPUnit\Framework\isEmpty;
use function PHPUnit\Framework\isNull;

class StoreWithdrawRequest extends FormRequest
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
            'bank' => 'required|string|exists:bank_accounts,id',
            'currency' => [
                'required',
                'string',
                Rule::in(['PEN', 'USD'])
            ],
            'amount' => [
                'required',
                'numeric',
                'gt:0',
                function ($attribute, $value, $fail) {

                    $amount = MoneyConverter::fromDecimal($value, $this->currency);

                    /** @var \App\Models\User $investor */
                    $investor = Auth::user();

                    $balance = $investor->getBalance($this->currency);
                    $balanceAmountMoney = MoneyConverter::fromDecimal($balance->amount, $this->currency);

                    if (!$balance) {
                        return $fail('El monedero seleccionado no existe.');
                    }

                    if ($balanceAmountMoney->lessThan($amount)) {
                        $fail('El monto no puede ser mayor a la cantidad disponible.');
                    }
                }
            ],
            'purpouse' => 'required|string',

        ];
    }

    /**
     * Get the proper failed validation response for the request.
     *
     * @param array<string, mixed> $errors
     */
    public function messages(): array
    {
        return [
            'bank.required' => 'Selecciona tu cuenta bancaria.',
            'purpouse.required' => 'El campo :attribute es requerido.',
            'amount.required' => 'El campo monto es requerido.',
            'amount.numeric' => 'El campo monto debe ser numérico.',
            'amount.gt' => 'El monto debe ser mayor a 0.',
            'currency.required' => 'Selecciona tu monedero.',
            'currency.in' => 'El campo monedero es incorrecto.',
        ];
    }
}
