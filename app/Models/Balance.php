<?php

namespace App\Models;
use App\Helpers\MoneyConverter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Money\Money;

class Balance extends Model
{
    use HasFactory, HasUlids;
    
    protected $table = 'balances';
    protected $fillable = [
        'amount',
        'invested_amount',
        'expected_amount',
        'currency',
        'investor_id',
    ];
    
    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }
    
    // ========================
    // Accessors (getters)
    // ========================
    public function getAmountAttribute(): string
    {
        return MoneyConverter::fromSubunitToDecimal(
            $this->attributes['amount'],
            $this->attributes['currency']
        );
    }
    
    public function getInvestedAmountAttribute(): string
    {
        return MoneyConverter::fromSubunitToDecimal(
            $this->attributes['invested_amount'],
            $this->attributes['currency']
        );
    }
    
    public function getExpectedAmountAttribute(): string
    {
        return MoneyConverter::fromSubunitToDecimal(
            $this->attributes['expected_amount'],
            $this->attributes['currency']
        );
    }
    
    public function getAmountMoney(): Money
    {
        return MoneyConverter::fromSubunit(
            $this->attributes['amount'] ?? 0,
            $this->attributes['currency']
        );
    }
    
    public function getInvestedAmountMoney(): Money
    {
        return MoneyConverter::fromSubunit(
            $this->attributes['invested_amount'] ?? 0,
            $this->attributes['currency']
        );
    }
    
    public function getExpectedAmountMoney(): Money
    {
        return MoneyConverter::fromSubunit(
            $this->attributes['expected_amount'] ?? 0,
            $this->attributes['currency']
        );
    }
    
    // ========================
    // Mutators (setters)
    // ========================
    public function setAmountAttribute(float | Money $value): void
    {
        if (!isset($this->attributes['currency'])) {
            throw new \RuntimeException('Currency must be set before amount');
        }
        
        if ($value instanceof Money) {
            $this->attributes['amount'] = $value->getAmount();
        } else {
            $this->attributes['amount'] = MoneyConverter::fromDecimal(
                $value,
                $this->attributes['currency']
            )->getAmount();
        }
    }
    
    public function setInvestedAmountAttribute(float | Money $value): void
    {
        if (!isset($this->attributes['currency'])) {
            throw new \RuntimeException('Currency must be set before amount');
        }
        
        if ($value instanceof Money) {
            $this->attributes['invested_amount'] = $value->getAmount();
        } else {
            $this->attributes['invested_amount'] = MoneyConverter::fromDecimal(
                $value,
                $this->attributes['currency']
            )->getAmount();
        }
    }
    
    public function setExpectedAmountAttribute(float | Money $value): void
    {
        if (!isset($this->attributes['currency'])) {
            throw new \RuntimeException('Currency must be set before amount');
        }
        
        if ($value instanceof Money) {
            $this->attributes['expected_amount'] = $value->getAmount();
        } else {
            $this->attributes['expected_amount'] = MoneyConverter::fromDecimal(
                $value,
                $this->attributes['currency']
            )->getAmount();
        }
    }
    
    // ========================
    // Money manipulation methods - CORREGIDOS
    // ========================
    public function addAmount(Money $amount): self
    {
        $newAmount = $this->getAmountMoney()->add($amount);
        // ✅ Usar el mutator en lugar de manipular attributes directamente
        $this->amount = $newAmount;
        return $this;
    }
    
    public function subtractAmount(Money $amount): self
    {
        $newAmount = $this->getAmountMoney()->subtract($amount);
        // ✅ Usar el mutator en lugar de manipular attributes directamente
        $this->amount = $newAmount;
        return $this;
    }
    
    public function addInvestedAmount(Money $amount): self
    {
        $newAmount = $this->getInvestedAmountMoney()->add($amount);
        // ✅ Usar el mutator en lugar de manipular attributes directamente
        $this->invested_amount = $newAmount;
        return $this;
    }
    
    public function addExpectedAmount(Money $amount): self
    {
        $newAmount = $this->getExpectedAmountMoney()->add($amount);
        // ✅ Usar el mutator en lugar de manipular attributes directamente
        $this->expected_amount = $newAmount;
        return $this;
    }
    
    public function subtractInvestedAmount(Money $amount): self
    {
        $newAmount = $this->getInvestedAmountMoney()->subtract($amount);
        // ✅ Usar el mutator en lugar de manipular attributes directamente
        $this->invested_amount = $newAmount;
        return $this;
    }
    
    public function subtractExpectedAmount(Money $amount): self
    {
        $newAmount = $this->getExpectedAmountMoney()->subtract($amount);
        // ✅ Usar el mutator en lugar de manipular attributes directamente
        $this->expected_amount = $newAmount;
        return $this;
    }
    
    // ========================
    // Utility methods
    // ========================
    public function hasEnoughBalance(Money $amount): bool
    {
        return $this->getAmountMoney()->greaterThanOrEqual($amount);
    }
}