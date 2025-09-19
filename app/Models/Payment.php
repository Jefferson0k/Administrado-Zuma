<?php

namespace App\Models;

use App\Enums\MovementStatus;
use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Helpers\MoneyConverter;
use Money\Money;

class Payment extends Model{
  use HasFactory;

  protected $fillable = [
      'invoice_id',
      'status',
      'pay_type',
      'amount_to_be_paid',
      'pay_date',
      'reprogramation_date',
      'reprogramation_rate',

      // Evidencias
      'evidencia',
      'evidencia_data',
      'evidencia_count',
      'evidencia_path',
      'evidencia_original_name',
      'evidencia_size',
      'evidencia_mime_type',

      // Aprobación nivel 1
      'approval1_status',
      'approval1_by',
      'approval1_comment',
      'approval1_at',

      // Aprobación nivel 2
      'approval2_status',
      'approval2_by',
      'approval2_comment',
      'approval2_at',
  ];

  public $timestamps = true;

  public function invoice(): BelongsTo
  {
    return $this->belongsTo(Invoice::class);
  }

  public function getAmountToBePaidMoney(): Money
  {
    return MoneyConverter::fromDecimal($this->amount_to_be_paid, $this->invoice->currency);
  }

  /**
   * Create total payments
   *
   * @return array[CustomError|null,null]
   */
  public function createTotalPayments(Invoice $invoice): array{
        $items = [];

        /**
         * Monto disponible a pagar de la factura
         */
        $availableAmount = $invoice->getAvailablePaidAmount();

        /**
         * Verificamos si la factura contiene pagos parciales
         */
        $isContainPartialPayments = $invoice->containPartialPayments();

        /**
         * Obtenemos todas las inversiones activas y reprogramadas
         */
        $activeInvestments = $invoice->investments
            ->whereIn('status', ['active'])
            ->sortBy('created_at');

        if ($isContainPartialPayments) {
            /**
             * Procesa y actualiza los balances de inversionistas con múltiples inversiones en la factura
             *
             * El proceso consiste en:
             * 1. Obtener el balance actual (billetera) del inversionista en la moneda de la factura
             * 2. Para cada inversión activa:
             *    - Calcular y acumular los retornos esperados de inversiones reprogramadas
             *    - Actualizar el capital disponible sumando el monto invertido
             *    - CAMBIO: Restar los retornos esperados del balance en lugar de sumarlos
             *    - Ajustar el monto invertido en el balance
             * 3. Como resultado:
             *    - Se liberan los fondos invertidos
             *    - Se registran los retornos generados SIN ITF
             *    - Se actualiza el balance consolidado del inversionista
             */
            foreach ($activeInvestments as $investment) {
                $investmentAmountMoney = MoneyConverter::fromDecimal($investment->amount, $invoice->currency);
                $investmentReturnMoney = MoneyConverter::fromDecimal($investment->return, $invoice->currency);
                $investor = $investment->investor;
                $balance = $investor->getBalance($invoice->currency);
                $originalInvestmentId = $investment->original_investment_id;

                $accumulatedExpectedReturnAmount = $invoice->investments()
                    ->where('status', 'reprogramed')
                    ->where('investor_id', $investor->id)
                    ->where(function ($query) use ($originalInvestmentId) {
                        $query->where('original_investment_id', $originalInvestmentId)
                            ->orWhere('id', $originalInvestmentId);
                    })
                    ->sum('return');
                $accumulatedExpectedReturnAmount = MoneyConverter::fromSubunit($accumulatedExpectedReturnAmount, $invoice->currency);
                $accumulatedExpectedReturnAmount = $accumulatedExpectedReturnAmount->add($investmentReturnMoney);

                /**
                 * CAMBIO: Sin ITF - El inversionista recibe el retorno completo
                 * Antes se calculaba el ITF (5%) sobre el retorno acumulado
                 * Ahora el retorno neto = retorno bruto (sin descuentos)
                 */
                $grossAccumulatedReturn = $accumulatedExpectedReturnAmount;
                $netAccumulatedReturn = $grossAccumulatedReturn; // Sin ITF

                /**
                 * Actualizamos el balance del inversionista:
                 * 1. Añadimos el monto de la inversión (capital)
                 * 2. Añadimos el retorno completo (sin ITF)
                 * 3. Reducimos el monto invertido
                 * 4. CAMBIO: Restamos el retorno esperado en lugar de sumarlo
                 */
                $balanceAmountMoney = MoneyConverter::fromDecimal($balance->amount, $invoice->currency);
                $balanceAmountMoney = $balanceAmountMoney->add($investmentAmountMoney);
                $balanceAmountMoney = $balanceAmountMoney->add($netAccumulatedReturn);
                $balance->amount = $balanceAmountMoney;

                $balanceInvestedAmountMoney = MoneyConverter::fromDecimal($balance->invested_amount, $invoice->currency);
                $balance->invested_amount = $balanceInvestedAmountMoney->subtract($investmentAmountMoney);

                $balanceExpectedAmountMoney = MoneyConverter::fromDecimal($balance->expected_amount, $invoice->currency);
                // CAMBIO PRINCIPAL: Restamos el retorno esperado en lugar de sumarlo
                $balance->expected_amount = $balanceExpectedAmountMoney->subtract($netAccumulatedReturn);
                $balance->save();

                /**
                 * Registramos el movimiento del capital invertido
                 */
                $movement = new Movement();
                $movement->currency = $invoice->currency;
                $movement->amount = $investmentAmountMoney;
                $movement->investor_id = $investor->id;
                $movement->type = MovementType::PAYMENT;
                $movement->status = MovementStatus::VALID;
                $movement->confirm_status = MovementStatus::VALID;
                $movement->description = 'Pago total restante de la factura';
                $movement->save();

                /**
                 * CAMBIO: No registramos movimiento de ITF ya que no se aplica
                 * Antes se registraba un movimiento de tipo TAX con el 5% del retorno
                 */

                /**
                 * Registramos el movimiento del retorno completo (sin ITF)
                 */
                $movement = new Movement();
                $movement->currency = $invoice->currency;
                $movement->amount = $netAccumulatedReturn;
                $movement->investor_id = $investor->id;
                $movement->type = MovementType::PAYMENT;
                $movement->status = MovementStatus::VALID;
                $movement->confirm_status = MovementStatus::VALID;
                $movement->description = 'Pago del retorno completo acumulado';
                $movement->save();

                // tambien cambiamos el status de la inversion a 'paid' y procedemos a guardar
                $investment->status = 'paid';
                $investment->save();

                array_push($items, [
                    'investor' => $investor,
                    'investment' => $investment,
                    'invested_amount' => $investment->amount,
                    'expected_return' => $netAccumulatedReturn,
                    'net_expected_return' => $netAccumulatedReturn, // Ahora es igual a expected_return
                    'itf_amount' => MoneyConverter::fromDecimal(0, $invoice->currency), // CAMBIO: ITF = 0
                    'balance' => $balance,
                ]);
            }
        } else {
            /**
             * Procesa los balances de los inversionistas con inversiones activas:
             *
             * 1. Para cada inversión activa:
             *    - Obtiene el balance del inversionista en la moneda de la factura
             *    - Incrementa el monto disponible con el capital + retorno completo
             *    - Reduce el monto invertido por el capital inicial
             *    - CAMBIO: Reduce el retorno esperado por el retorno calculado
             *
             * 2. Los montos se actualizan en la billetera del inversionista:
             *    - amount: Incrementa con capital + retorno completo (sin ITF)
             *    - invested_amount: Reduce por el capital inicial
             *    - expected_amount: CAMBIO - Reduce por el retorno calculado
             *
             * 3. Esto permite liberar los fondos invertidos y registrar
             *    las ganancias completas en la billetera del inversionista
             */
            foreach ($activeInvestments as $investment) {
                $investor = $investment->investor;
                $investmentAmountMoney = MoneyConverter::fromDecimal($investment->amount, $invoice->currency);
                $balance = $investor->getBalance($invoice->currency);

                /**
                 * CAMBIO: Sin ITF - El retorno es completo
                 * Antes se calculaba el ITF (5%) sobre el retorno esperado
                 * Ahora el retorno neto = retorno bruto (sin descuentos)
                 */
                $grossReturn = MoneyConverter::fromDecimal($investment->return, $invoice->currency);
                $netReturn = $grossReturn; // Sin ITF

                /**
                 * Actualizamos el balance del inversionista:
                 * 1. Añadimos el monto de la inversión (capital)
                 * 2. Añadimos el retorno completo (sin ITF)
                 * 3. Reducimos el monto invertido
                 * 4. CAMBIO: Restamos el retorno esperado
                 */
                $balanceAmountMoney = MoneyConverter::fromDecimal($balance->amount, $invoice->currency);
                $balanceAmountMoney = $balanceAmountMoney->add($investmentAmountMoney);
                $balanceAmountMoney = $balanceAmountMoney->add($netReturn);
                $balance->amount = $balanceAmountMoney;

                $balanceInvestedAmountMoney = MoneyConverter::fromDecimal($balance->invested_amount, $invoice->currency);
                $balance->invested_amount = $balanceInvestedAmountMoney->subtract($investmentAmountMoney);

                $balanceExpectedAmountMoney = MoneyConverter::fromDecimal($balance->expected_amount, $invoice->currency);
                // CAMBIO PRINCIPAL: Restamos el retorno esperado en lugar de sumarlo
                $balance->expected_amount = $balanceExpectedAmountMoney->subtract($netReturn);

                /**
                 * Registramos el movimiento del capital invertido
                 */
                $movement = new Movement();
                $movement->currency = $invoice->currency;
                $movement->amount = $investmentAmountMoney;
                $movement->investor_id = $investor->id;
                $movement->type = MovementType::PAYMENT;
                $movement->status = MovementStatus::VALID;
                $movement->confirm_status = MovementStatus::VALID;
                $movement->description = 'Pago total de la factura';
                $movement->save();

                /**
                 * CAMBIO: No registramos movimiento de ITF ya que no se aplica
                 * Antes se registraba un movimiento de tipo TAX con el 5% del retorno
                 */

                /**
                 * Registramos el movimiento del retorno completo (sin ITF)
                 */
                $movement = new Movement();
                $movement->currency = $invoice->currency;
                $movement->amount = $netReturn;
                $movement->investor_id = $investor->id;
                $movement->type = MovementType::PAYMENT;
                $movement->status = MovementStatus::VALID;
                $movement->confirm_status = MovementStatus::VALID;
                $movement->description = 'Pago del retorno completo de la inversión';
                $movement->save();

                // aqui guardamos el balance actualizado
                $balance->save();
                // tambien cambiamos el status de la inversion a 'paid' y procedemos a guardar
                $investment->status = 'paid';
                $investment->save();

                array_push($items, [
                    'investor' => $investor,
                    'investment' => $investment,
                    'invested_amount' => $investment->amount,
                    'expected_return' => $investment->return,
                    'net_expected_return' => $netReturn, // Ahora es igual a expected_return
                    'itf_amount' => MoneyConverter::fromDecimal(0, $invoice->currency), // CAMBIO: ITF = 0
                    'balance' => $balance,
                ]);
            }
        }

        return [null, [
            'items' => $items,
        ]];
    }


  public function createPartialPayments(
    Invoice $invoice,
    float $apportionedPercentage, // porcentaje a pagar del total disponible
    float $invesmentRate // tasa de la nueva inversion factura
  ): array {
    $items = [];

    /**
     * Tipo de moneda de la factura
     * campo: currency
     */
    $currency = $invoice->currency;

    /**
     * 1. Monto disponible de la facura para pagar
     * Formula: invoice_available_amount = invoice_amount - invoice_paid_amount
     */
    $newInvoiceAvailableAmount = $invoice->getAvailablePaidAmount();

    /**
     * Obtenemos todas las inversiones activas
     */
    $investments = $invoice->investments->where('status', 'active');

    /** @var Investment $investment */
    foreach ($investments as $investment) {
      /**
       * Cantidad invertida por el inversionista
       */
      $investmentAmount = MoneyConverter::fromDecimal($investment->amount, $currency);

      /**
       * Monto a pagar al inversionista
       * se obtiene de la cantidad de la inversion, multiplicada por el porcentaje de pago
       */
      $amountToPay = $investmentAmount->multiply((string) $apportionedPercentage);

      /**
       * Nuevo monto en fondos invertidos
       * Formula: new_invesment_amount = investment_amount - amount_to_pay
       */
      $newInvestmentAmount = $investmentAmount->subtract($amountToPay);

      /**
       * Retorno esperado por el inversionista
       * Formula: expected_return = new_invesment_amount * new_percent_rate
       */
      $newExpectedReturn = $newInvestmentAmount->multiply((string) $invesmentRate);


      // push to items
      array_push($items, [
        'investor'            => $investment->investor,
        'investment'          => $investment,
        'amountToPay'         => $amountToPay,
        'newInvestmentAmount' => $newInvestmentAmount,
        'newExpectedReturn'   => $newExpectedReturn,
      ]);
    }

    return [null, [
      'items'                     => $items, // items de pagos parciales
      'newInvoiceAvailableAmount' => $newInvoiceAvailableAmount, // nuevo monto disponible de la factura
    ]];
  }
}
