<?php

namespace App\Models;

use App\Enums\Currency;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Money\Money;
use App\Helpers\MoneyConverter;

class Exchange extends Model{
    use HasFactory, HasUlids;
    protected $fillable = ['exchange_rate_sell', 'exchange_rate_buy', 'currency', 'status'];
    /**
     * Get current exchange rate
     *
     * @return ?Exchange
     */
    public static function getCurrentExchangeRate(): ?Exchange
    {
        return self::where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * The attributes that should be cast.
     * Ingresa el monto en PEN 
     * Retorna el monto en USD
     *
     * @var array<string, string>
     * @return Money
     */
    public function penToUsd(Money $amount): Money
    {
        $amount =  $amount->divide((string) $this->exchange_rate_sell);
        return MoneyConverter::fromSubunit($amount->getAmount(), Currency::USD->value);
    }

    /**
     * The attributes that should be cast.
     * Ingresa el monto en USD
     * Retorna el monto en PEN
     *
     * @var array<string, string>
     * @return Money
     */
    public function usdToPen(Money $amount): Money{
        $amount = $amount->multiply((string) $this->exchange_rate_buy);
        return MoneyConverter::fromSubunit($amount->getAmount(), Currency::PEN->value);
    }
    // ========================
    // Accesores (getters)
    // ========================
    public function getExchangeRateSellAttribute(): string{
        return MoneyConverter::fromSubunitToDecimal(
            $this->attributes['exchange_rate_sell'],
            $this->attributes['currency']
        );
    }
    public function getExchangeRateBuyAttribute(): string{
        return MoneyConverter::fromSubunitToDecimal(
            $this->attributes['exchange_rate_buy'],
            $this->attributes['currency']
        );
    }
    // ========================
    // Accesores (setters)
    // ========================
    public function setExchangeRateSellAttribute(float | Money $value): void{
        if (!isset($this->attributes['currency'])) {
            throw new \RuntimeException('Currency must be set before exchange rate sell');
        }
        if ($value instanceof Money) {
            $this->attributes['exchange_rate_sell'] = $value->getAmount();
        } else {
            $this->attributes['exchange_rate_sell'] = MoneyConverter::fromDecimal(
                $value,
                $this->attributes['currency']
            )->getAmount();
        }
    }
    public function setExchangeRateBuyAttribute(float | Money $value): void{
        if (!isset($this->attributes['currency'])) {
            throw new \RuntimeException('Currency must be set before exchange rate buy');
        }
        if ($value instanceof Money) {
            $this->attributes['exchange_rate_buy'] = $value->getAmount();
        } else {
            $this->attributes['exchange_rate_buy'] = MoneyConverter::fromDecimal(
                $value,
                $this->attributes['currency']
            )->getAmount();
        }
    }
}
