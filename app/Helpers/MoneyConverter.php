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
 * @version 1.0.1
 * @since   2025-05-26
 */
class MoneyConverter
{
    /**
     * Crea un objeto Money a partir de un valor decimal
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

        $subunits = (int) strval(round($amount, $decimals) * (10 ** $decimals));
        return new Money($subunits, $currencyObj);
    }

    /**
     * Convierte un objeto Money a un valor decimal (float, para cálculos internos)
     */
    public static function toDecimal(Money $money, int $decimals = 2): float
    {
        return (float) ($money->getAmount() / (10 ** $decimals));
    }

    /**
     * Convierte un objeto Money a un valor decimal (string, seguro para DB DECIMAL)
     */
    public static function toDatabase(Money $money, int $decimals = 2): string
    {
        return bcdiv($money->getAmount(), (string) (10 ** $decimals), $decimals);
    }

    /**
     * Crea un objeto Money a partir de un valor en subunidades
     */
    public static function fromSubunit(int|string $amount, string $currency = 'PEN'): Money
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
     */
    public static function toSubunit(Money $money): int|string
    {
        return $money->getAmount();
    }

    /**
     * Convierte un monto en subunidades a su valor decimal
     */
    public static function fromSubunitToDecimal(int|string $amount, string $currency = 'PEN', int $decimals = 2): string
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

        return bcdiv((string) $amount, (string) (10 ** $decimals), $decimals);
    }

    /**
     * Convierte un objeto Money a su valor decimal (string con precisión exacta)
     */
    public static function getValue(Money $money, int $decimals = 2): string
    {
        if ($decimals < 0) {
            throw new \InvalidArgumentException('Decimal places must be positive');
        }

        return bcdiv($money->getAmount(), (string) (10 ** $decimals), $decimals);
    }

    /**
     * Convierte un objeto Money a un array de datos útiles
     */
    public static function toArray(Money $money, int $decimals = 2): array
    {
        $currencyCode = $money->getCurrency()->getCode();
        $locale = $currencyCode === 'PEN' ? 'es_PE' : 'en_US';
        $value = self::getValue($money, $decimals);

        return [
            'subunits' => $money->getAmount(),
            'decimal' => $value,
            'currency' => [
                'code' => $currencyCode,
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
