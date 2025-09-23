<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Money\Money;
use App\Helpers\MoneyConverter;

class Withdraw extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'nro_operation',
        'amount',
        'currency',
        'deposit_pay_date',
        'resource_path',
        'description',
        'purpouse',
        'approval1_by',
        'approval1_status',
        'approval1_comment',
        'approval1_at',
        'approval2_by',
        'approval2_status',
        'approval2_comment',
        'approval2_at',
        'status',
        'created_by',
        'updated_by',

        'movement_id',
        'investor_id',
        'bank_account_id',
    ];

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }
    public function approvalUserOne()
    {
        return $this->belongsTo(User::class, 'approval1_by');
    }

    public function approvalUserTwo()
    {
        return $this->belongsTo(User::class, 'approval2_by');
}
    public function bank_account(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(Movement::class);
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
    public function getResourcePathAttribute(): ?string
    {
        if (isset($this->attributes['resource_path'])) {
            return env('APP_URL') . '/s3/' . $this->attributes['resource_path'];
        }
        return null;
    }

    // Si quieres conservar también el valor raw (sin URL)
    public function getResourcePathRaw(): ?string
    {
        return $this->attributes['resource_path'] ?? null;
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
    
}
