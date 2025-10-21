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
                'gte:50',
                function ($attribute, $value, $fail) {
                    /** @var \App\Models\User $investor */
                    $investor = Auth::user();

                    $balance = $investor->getBalance($this->currency);

                    if (!$balance) {
                        return $fail('El monedero seleccionado no existe.');
                    }

                    $investorBalanceAmountMoney = MoneyConverter::fromDecimal($balance->amount, $this->currency);
                    $amount = MoneyConverter::fromDecimal($value, $this->currency);

                    if ($investorBalanceAmountMoney->lessThan($amount)) {
                        return $fail('El monto no puede ser mayor a la cantidad disponible.');
                    }

                    // Obtener la factura
                    $invoice = Invoice::where('invoice_code', $this->invoice_code)
                        ->where('status', 'active')
                        ->first();

                    if (!$invoice) {
                        return $fail('La factura seleccionada no se ha encontrado.');
                    }

                    if (Carbon::parse($invoice->due_date)->isPast()) {
                        return $fail('La factura seleccionada ya no está disponible para invertir.');
                    }

                    // Convertir montos relevantes a Money (usar 0 si no existen)
                    $invoiceAmount = MoneyConverter::fromDecimal((float)$invoice->amount, $this->currency);
                    $financedByGarantia = MoneyConverter::fromDecimal((float)($invoice->financed_amount_by_garantia ?? 0), $this->currency);
                    $financedField = MoneyConverter::fromDecimal((float)($invoice->financed_amount ?? 0), $this->currency);

                    /**
                     * Detectar semántica de campos:
                     * - Si (invoiceAmount - financedByGarantia) == financedField entonces
                     *   `financed_amount` está representando el disponible (ej. 15000 - 100 = 14900)
                     *   En ese caso tomamos $available = financedField
                     * - En caso contrario asumimos la semántica "clásica":
                     *   financed_amount es lo ya financiado y el disponible = invoiceAmount - financed_amount
                     */
                    if ($invoiceAmount->subtract($financedByGarantia)->equals($financedField)) {
                        // Aquí financed_amount actúa como "disponible"
                        $available = $financedField;
                    } else {
                        // Aquí financed_amount actúa como "ya financiado", entonces disponible = amount - financed_amount
                        $available = $invoiceAmount->subtract($financedField);
                    }

                    // Si no hay disponible
                    if ($available->isZero()) {
                        return $fail('Ups! La factura ya no está disponible para invertir.');
                    }

                    // Validar solicitud contra el disponible calculado
                    if ($amount->greaterThan($available)) {
                        return $fail('Monto disponible para invertir es: ' . MoneyFormatter::format($available));
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
                    $invoiceExists = Invoice::where('invoice_code', $value)
                        ->where('status', 'active')
                        ->first();
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
            'amount.gte' => 'El monto debe ser mayor o igual a 50',
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