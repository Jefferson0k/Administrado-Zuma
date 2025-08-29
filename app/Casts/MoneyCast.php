<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Money\Money;
use App\Helpers\MoneyConverter;

class MoneyCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        return MoneyConverter::fromDecimal($value, $attributes['currency'] ?? 'PEN');
    }

    public function set($model, string $key, $value, array $attributes): ?string
    {
        if ($value instanceof Money) {
            return MoneyConverter::toDecimal($value);
        }

        return $value;
    }
}
