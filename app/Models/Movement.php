<?php

namespace App\Models;

use App\Enums\MovementStatus;
use App\Enums\MovementType;
use App\Helpers\MoneyConverter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Money\Money;
use NumberFormatter;

class Movement extends Model{
    use HasFactory;
    protected $table = 'movements';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'amount',
        'type',
        'currency',
        'status',
        'confirm_status',
        'description',
        'investor_id',
        'currency',
    ];
    protected $casts = [
        'type' => MovementType::class,
        'status' => MovementStatus::class,
        'confirm_status' => MovementStatus::class,
        'amount' => 'decimal:2',
    ];
    public function investor(){
        return $this->belongsTo(Investor::class);
    }
    public function deposit(){
        return $this->hasOne(Deposit::class, 'movement_id');
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::ulid()->toBase32();
            }
        });
    }
    public function deposits()
    {
        return Movement::where('type', 'deposit')->get();
    }

    public function payments()
    {
        return Movement::where('type', 'payment')->get();
    }

    public function withdraws()
    {
        return Movement::where('type', 'withdraw')->get();
    }
    public function investment(){
        return $this->hasOne(Investment::class, 'movement_id', 'id');
    }

    public function bank_account(){
        return $this->belongsTo(BankAccount::class);
    }

    // ========================
    // Accesores (getters)
    // ========================

    public function getAmountAttribute(): string
    {
        return MoneyConverter::fromSubunitToDecimal(
            $this->attributes['amount'],
            $this->attributes['currency']
        );
    }

    // ========================
    // Accesores (setters)
    // ========================

    public function setAmountAttribute(float | Money $value): void{
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
    public function getAmountFormattedAttribute(): string{
        $currency = $this?->bank_account?->currency ?: $this->currency;
        $amount = $this->attributes['amount'];
        $locale = $currency === 'PEN' ? 'es_PE' : 'en_US';
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($amount, $currency);
    }
    public function sendDepositRejectedEmail(string $message){
        $this->investor->sendDepositRejectedEmail($this, $message);
    }
}
