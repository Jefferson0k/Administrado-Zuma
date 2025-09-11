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
        $disk = Storage::disk('s3');
        $path = $this->attributes['resource_path'] ?? null;

        if (empty($path)) {
            return null;
        }

        try {
            // Solo usar exists() en drivers que lo soportan bien
            if ($disk->getDriver()->getAdapter() instanceof \League\Flysystem\AwsS3V3\AwsS3Adapter) {
                // En S3: asumir que el archivo existe y devolver directamente la URL
                return $disk->temporaryUrl($path, now()->addMinutes(10));
            } else {
                // En local u otros: sí validamos la existencia
                if ($disk->exists($path)) {
                    return $disk->temporaryUrl($path, now()->addMinutes(10));
                }
            }
        } catch (\Throwable $e) {
            // Si algo falla, mejor devolver null para no romper la app
            return null;
        }

        return null;
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
