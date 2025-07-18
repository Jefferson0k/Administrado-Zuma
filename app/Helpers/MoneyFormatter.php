<?php

namespace App\Helpers;

use Money\Money;
use Money\Currency;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Money\Formatter\DecimalMoneyFormatter;
use NumberFormatter;

class MoneyFormatter
{
  private static array $formatters = [];

  /**
   * Formatea un objeto Money a una cadena de texto con el formato de moneda local
   * @param Money $money
   * @param IntlMoneyFormatter $formatter
   * @return string
   */
  public static function format(Money $money, ?IntlMoneyFormatter $formatter = null): string
  {
    if ($formatter !== null) {
      return $formatter->format($money);
    }

    $currencyCode = $money->getCurrency()->getCode();
    $locale = $currencyCode === 'PEN' ? 'es_PE' : 'en_US';

    if (!isset(self::$formatters[$locale])) {
      self::$formatters[$locale] = new IntlMoneyFormatter(
        new NumberFormatter($locale, NumberFormatter::CURRENCY),
        new ISOCurrencies()
      );
    }

    return self::$formatters[$locale]->format($money);
  }

  /**
   * Formatea un monto en subunidades a una cadena de texto con el formato de moneda local
   * @param int | string $amount Monto en subunidades
   * @param string $currency Código de la moneda (default: 'PEN')
   * @param int $decimals Número de decimales (default: 2)
   * @return string Valor formateado
   */
  public static function formatFromDecimal(float | string $amount, string $currency = 'PEN', int $decimals = 2): string
  {
    $money = MoneyConverter::fromDecimal($amount, $currency, $decimals);
    return self::format($money);
  }

  /**
   * Obtiene un formateador para un locale específico
   * @param string $locale
   * @return IntlMoneyFormatter
   */
  public static function getFormatterForLocale(string $locale): IntlMoneyFormatter
  {
    if (!isset(self::$formatters[$locale])) {
      self::$formatters[$locale] = new IntlMoneyFormatter(
        new NumberFormatter($locale, NumberFormatter::CURRENCY),
        new ISOCurrencies()
      );
    }
    return self::$formatters[$locale];
  }
}
