<?php

namespace App\Casts;

use Money\Money;
use App\Helpers\MoneyConverter;
use App\Helpers\MoneyFormatter;

class MoneyCast
{
    public function __construct(
        public Money $money,
    ) {}

    public function toArray(): array
    {
        return [
            'amount'    => $this->money->getAmount(),
            'currency'  => $this->money->getCurrency()->getCode(),
            'formatted' => MoneyFormatter::format($this->money),
            'value'     => MoneyConverter::getValue($this->money),
        ];
    }

    public static function fromArray(array $value): static
    {
        $money = MoneyConverter::fromDecimal($value['value'], $value['currency']);
        return new self($money);
    }
}
