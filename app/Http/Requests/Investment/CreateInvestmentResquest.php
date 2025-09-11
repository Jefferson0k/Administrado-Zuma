<?php

namespace App\Http\Requests\Investment;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Helpers\MoneyConverter;
use App\Helpers\MoneyFormatter;
use Carbon\Carbon;

class CreateInvestmentResquest extends FormRequest
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
            'amount' => [
                'required',
                'numeric',
                'gte:100',
                function ($attribute, $value, $fail) {

                    /** @var \App\Models\User $investor */
                    $investor = Auth::user();

                    $balance = $investor->getBalance($this->currency);
                    $investorBalanceAmountMoney = MoneyConverter::fromDecimal($balance->amount, $this->currency);

                    $amount = MoneyConverter::fromDecimal($value, $this->currency);

                    if (!$balance) {
                        return $fail('El monedero seleccionado no existe.');
                    }

                    if ($investorBalanceAmountMoney->lessThan($amount)) {
                        return $fail('El monto no puede ser mayor a la cantidad disponible.');
                    }

                    // Validación: monto + financed_amount no debe superar amount de la factura
                    $invoice = Invoice::where('invoice_code', $this->invoice_code)->where('status', 'active')->first();

                    if (!$invoice) {
                        return $fail('La factura seleccionada no se ha encontrado.');
                    }

                    if (Carbon::parse($invoice->due_date)->isPast()) {
                        return $fail('La factura seleccionada ya no está disponible para invertir.');
                    }

                    if ($invoice) {
                        $invoiceAmount = MoneyConverter::fromDecimal((float)$invoice->amount, $this->currency);
                        $invoiceFinanced = MoneyConverter::fromDecimal((float)$invoice->financed_amount, $this->currency);
                        $newFinanced = $invoiceFinanced->add($amount);

                        $availableAmount = MoneyFormatter::format($invoiceAmount->subtract($invoiceFinanced));

                        /** Si el monto disponible es igual a 0
                         * Entonces devolvemos un mensaje:
                         * "Ups! La factura ya no está disponible para invertir."
                         */
                        if ($invoiceAmount->equals($invoiceFinanced)) {
                            return $fail('Ups! La factura ya no está disponible para invertir.');
                        }

                        if ($newFinanced->greaterThan($invoiceAmount)) {
                            return $fail('Monto disponible para invertir es: ' . $availableAmount);
                        }
                    }
                }
            ],
            'expected_return' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'currency' => [
                'required',
                Rule::in(['PEN', 'USD']),
            ],
            'invoice_code' => [
                'required',
                'string',
                'exists:invoices,invoice_code',
                function ($attribute, $value, $fail) {
                    $invoiceExists = Invoice::where('invoice_code', $value)->where('status', 'active')->first();
                    if (!$invoiceExists) {
                        return $fail('La factura seleccionada no existe.');
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [
            'amount.required' => 'El monto es obligatorio',
            'amount.numeric' => 'El monto debe ser numérico',
            'amount.gte' => 'El monto debe ser mayor o igual a 100',
            'currency.required' => 'La moneda es obligatoria',
            'currency.in' => 'Seleccione una moneda válida',
            'invoice_code.required' => 'El código de factura es obligatorio',
            'invoice_code.exists' => 'El código de factura no existe',
            'expected_return.required' => 'El retorno esperado es obligatorio',
            'expected_return.numeric' => 'El retorno esperado debe ser numérico',
            'expected_return.gt' => 'El retorno esperado debe ser mayor a 0',
        ];
    }
}
