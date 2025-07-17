<?php

namespace App\Models;

use App\Enums\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Deposit extends Model{
    use HasFactory;

    protected $table = 'deposits';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
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
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'currency' => Currency::class,
    ];

    /**
     * Relaciones
     */

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

}
