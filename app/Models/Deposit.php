<?php

namespace App\Models;

use App\Enums\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
class Deposit extends Model{
    use HasFactory;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $table = 'deposits';
    protected $fillable = [
        'id',
        'nro_operation',
        'amount',
        'currency',
        'resource_path',
        'description',
        'investor_id',
        'movement_id',
        'bank_account_id',
        'payment_source',
        'type',
        'created_by',
        'updated_by',
        'fixed_term_investment_id',
        'payment_schedules_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'currency' => Currency::class,
    ];

    /**
     * Relaciones
     */

    protected static function boot(){
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::ulid();
            }
        });
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(Movement::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
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
}
