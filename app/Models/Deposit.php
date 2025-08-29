<?php

namespace App\Models;

use App\Helpers\MoneyConverter;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Money\Money;

class Deposit extends Model{
    use HasFactory, HasUlids;
    protected $fillable = [
        'nro_operation',
        'currency',
        'amount',
        'resource_path',
        'description',
        'movement_id',
        'investor_id',
        'bank_account_id',
        'created_by',
        'updated_by',
        'fixed_term_investment_id',
        'property_reservations_id'
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function movement(): BelongsTo
    {
        return $this->belongsTo(Movement::class);
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    protected static function boot(){
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::ulid();
            }
        });
    }
    public function fixedTermInvestment(){
        return $this->belongsTo(FixedTermInvestment::class);
    }
    public function propertyreservacion(){
        return $this->belongsTo(PropertyReservation::class);
    }
    public function paymentschedules(){
        return $this->belongsTo(PaymentSchedule::class);
    }
    // ========================
    // Accesores (getters)
    // ========================
    public function getAmountAttribute(): string{
        return MoneyConverter::fromSubunitToDecimal(
            $this->attributes['amount'],
            $this->attributes['currency']
        );
    }
    public function getResourcePathAttribute(): ?string{
        if (isset($this->attributes['resource_path'])) {
            return env('APP_URL') . '/s3/' . $this->attributes['resource_path'];
        }
        return null;
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
}
