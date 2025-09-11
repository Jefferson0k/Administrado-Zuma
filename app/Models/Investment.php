<?php

namespace App\Models;

use App\Helpers\MoneyConverter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Money\Money;
use NumberFormatter;

class Investment extends Model{
    use HasFactory;
    protected $table = 'investments';
    protected $fillable = [
        'amount',
        'return',
        'rate',
        'currency',
        'due_date',
        'status',
        'investor_id',
        'invoice_id',
        'movement_id',
        'previous_investment_id',
        'original_investment_id',
        'operation_number',
        'receipt_path',
        'comment',
    ];
    public function investor(){
        return $this->belongsTo(Investor::class);
    }
    public function property(){
        return $this->belongsTo(Property::class);
    }
    public function invoice(){
        return $this->belongsTo(Invoice::class);
    }

    public function relatedInvestments(){
        return $this->hasMany(self::class, 'previous_investment_id');
    }

    public function previousInvestment(){
        return $this->belongsTo(self::class, 'previous_investment_id');
    }

    public function originalInvestment(){
        return $this->belongsTo(self::class, 'original_investment_id');
    }

    public function movement(){
        return $this->belongsTo(Movement::class);
    }

    public function getAmountFormattedAttribute(): string
    {
        $currency = $this->attributes['currency'];
        $amount = $this->attributes['amount'];

        $locale = $currency === 'PEN' ? 'es_PE' : 'en_US';
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($amount, $currency);
    }

    public function getReturnFormattedAttribute(): string
    {
        $currency = $this->attributes['currency'];
        $amount = $this->attributes['return'];

        $locale = $currency === 'PEN' ? 'es_PE' : 'en_US';
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($amount, $currency);
    }

    // ========================
    //  Accesores (getters)
    // ========================

    public function getAmountAttribute(): string
    {
        return MoneyConverter::fromSubunitToDecimal(
            $this->attributes['amount'],
            $this->attributes['currency']
        );
    }

    public function getReturnAttribute(): string
    {
        return MoneyConverter::fromSubunitToDecimal(
            $this->attributes['return'],
            $this->attributes['currency']
        );
    }

    // ========================
    // Accesores (setters)
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

    public function setReturnAttribute(float | Money $value): void
    {
        if (!isset($this->attributes['currency'])) {
            throw new \RuntimeException('Currency must be set before return');
        }

        if ($value instanceof Money) {
            $this->attributes['return'] = $value->getAmount();
        } else {
            $this->attributes['return'] = MoneyConverter::fromDecimal(
                $value,
                $this->attributes['currency']
            )->getAmount();
        }
    }
}