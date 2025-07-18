<?php

namespace App\Helpers;

use Money\Money;
use Money\Currency;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Money\Formatter\DecimalMoneyFormatter;
use NumberFormatter;

/**
 * Money helper class
 * Utiliza el paquete moneyphp/money para manejar y formatear monedas
 * https://github.com/moneyphp/money
 *
 * @package App\Helpers
 * @author Apros
 * @version 1.0.0
 * @since   2025-05-26
 * @example
 * // ❌ Incorrecto (pérdida de precisión)
 * $mal = Money::PEN(100.50 * 100); // Puede resultar en 10049 o 10051.
 *
 * // ✅ Correcto (usa round y casteo a int)
 * $bien = Money::PEN((int) round(100.50 * 100, 0)); // 10050
 *
 * @license MIT
 */

class MoneyConverter
{
  /**
   * Crea un objeto Money a partir de un valor decimal
   * @param float $amount
   * @param string $currency
   * @param int $decimals
   * @return Money
   */
  public static function fromDecimal(float $amount, string $currency = 'PEN', int $decimals = 2): Money
  {
    if ($amount < 0) {
      throw new \InvalidArgumentException(sprintf(
        "Error en MoneyConverter::fromDecimal - El monto %s %s no puede ser negativo.",
        $amount,
        $currency
      ));
    }

    $currencyObj = new Currency($currency);
    if (!(new ISOCurrencies())->contains($currencyObj)) {
      throw new \InvalidArgumentException("Divisa no soportada: $currency");
    }

    // 1. Redondear primero al número de decimales correcto
    // 2. Multiplicar después para evitar errores acumulativos
    // 3. Usar strval como "puente" para evitar interpretaciones erróneas
    $subunits = (int) strval(round($amount, $decimals) * (10 ** $decimals));
    return new Money($subunits, $currencyObj);
  }



  /**
   * Convierte un objeto Money a un valor decimal
   * @param Money $money
   * @param int $decimals
   * @return float
   */
  public static function toDecimal(Money $money, int $decimals = 2): float
  {
    return $money->getAmount() / (10 ** $decimals);
  }


  /**
   * Crea un objeto Money a partir de un valor en subunidades
   * @param int | string $amount
   * @param string $currency
   * @return Money
   */
  public static function fromSubunit(int | string $amount, string $currency = 'PEN'): Money
  {
    if ($amount < 0) {
      throw new \InvalidArgumentException(sprintf(
        "Error en MoneyConverter::fromSubunit - El monto %s %s no puede ser negativo.",
        $amount,
        $currency
      ));
    }

    $currencyObj = new Currency($currency);
    if (!(new ISOCurrencies())->contains($currencyObj)) {
      throw new \InvalidArgumentException("Divisa no soportada: $currency");
    }

    return new Money($amount, $currencyObj);
  }

  /**
   * Convierte un objeto Money a un valor en subunidades
   * @param Money $money
   * @return int | string
   */
  public static function toSubunit(Money $money): int | string
  {
    return $money->getAmount();
  }

  /**
   * Convierte un monto en subunidades a su valor decimal
   * @param int | string $amount Monto en subunidades
   * @param string $currency Código de la moneda (default: 'PEN')
   * @param int $decimals Número de decimales (default: 2)
   * @return string Valor decimal formateado
   * @throws InvalidArgumentException Si los decimales son negativos o la divisa no es soportada
   */
  public static function fromSubunitToDecimal(int | string $amount, $currency = 'PEN', int $decimals = 2): string
  {
    if ($amount < 0) {
      throw new \InvalidArgumentException(sprintf(
        "Error en MoneyConverter::fromSubunitToDecimal - El monto %s %s no puede ser negativo.",
        $amount,
        $currency
      ), 400);
    }

    $currencyObj = new Currency($currency);
    if (!(new ISOCurrencies())->contains($currencyObj)) {
      throw new \InvalidArgumentException("Divisa no soportada: $currency");
    }

    // Convertir subunidades a decimal
    return bcdiv((string) $amount, (string) (10 ** $decimals), $decimals);
  }


  /**
   * Convierte un objeto Money a su valor decimal
   * @param Money $money Objeto Money a convertir
   * @param int $decimals Número de decimales (default: 2)
   * @return float Valor decimal formateado
   * @throws InvalidArgumentException Si los decimales son negativos
   */
  public static function getValue(Money $money, int $decimals = 2): string
  {
    if ($decimals < 0) {
      throw new \InvalidArgumentException('Decimal places must be positive');
    }

    $value = bcdiv($money->getAmount(), 10 ** $decimals, $decimals);
    return (string) $value;
  }


  public static function toArray(Money $money, int $decimals = 2): array
  {
    $currencyCode = $money->getCurrency()->getCode();
    $locale = $currencyCode === 'PEN' ? 'es_PE' : 'en_US';
    $value = self::getValue($money, $decimals);

    return [
      'subunits' => $money->getAmount(),
      'decimal' => $value,
      'currency' => [
        'code' => $money->getCurrency()->getCode(),
        'symbol' => (new NumberFormatter($locale, NumberFormatter::CURRENCY))->getSymbol(NumberFormatter::CURRENCY_SYMBOL),
        'decimals' => $decimals,
      ],
      'formatted' => [
        'full' => MoneyFormatter::format($money),
        'compact' => $value,
        'locale' => $locale,
      ],
    ];
  }
}
