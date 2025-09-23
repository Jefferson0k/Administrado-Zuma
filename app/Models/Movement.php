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
        'aprobacion_1',
        'aprobado_por_1',
        'aprobacion_2',
        'aprobado_por_2',
        'approval1_by',
        'approval2_by'
    ];
    protected $casts = [
        'type' => MovementType::class,
        'status' => MovementStatus::class,
        'confirm_status' => MovementStatus::class,
        'amount' => 'decimal:2',
        'aprobacion_1' => 'datetime',
        'aprobacion_2' => 'datetime',
    ];
    public function exchange(){
        return $this->belongsTo(Exchange::class, 'exchange_id', 'id');
    }
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
    public function aprobadoPorUsuario1(){
        return $this->belongsTo(User::class, 'approval1_by');
    }
    public function aprobadoPorUsuario2(){
        return $this->belongsTo(User::class, 'approval2_by');
    }
    public function deposits()
    {
        return Movement::where('type', 'deposit')->get();
    }

    public function withdraw()
    {
        return $this->hasOne(Withdraw::class, 'movement_id');
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
    // NUEVO: Relaciones para usuarios que aprobaron
    public function aprobadoPor1(){
        return $this->belongsTo(User::class, 'aprobado_por_1');
    }

    public function aprobadoPor2(){
        return $this->belongsTo(User::class, 'aprobado_por_2');
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
      public function getTiempoAprobacion1HorasAttribute(): ?float
    {
        if (!$this->aprobacion_1) {
            return null;
        }
        return $this->created_at->diffInHours($this->aprobacion_1);
    }

    public function getTiempoAprobacion2HorasAttribute(): ?float
    {
        if (!$this->aprobacion_1 || !$this->aprobacion_2) {
            return null;
        }
        return $this->aprobacion_1->diffInHours($this->aprobacion_2);
    }

    public function getTiempoTotalHorasAttribute(): ?float
    {
        if (!$this->aprobacion_2) {
            return null;
        }
        return $this->created_at->diffInHours($this->aprobacion_2);
    }

    public function registrarAprobacion1(string $usuarioId): void
    {
        $this->update([
            'aprobacion_1' => now(),
            'aprobado_por_1' => $usuarioId
        ]);
    }

    public function registrarAprobacion2(string $usuarioId): void
    {
        $this->update([
            'aprobacion_2' => now(),
            'aprobado_por_2' => $usuarioId
        ]);
    }

    public function scopePendienteAprobacion1($query)
    {
        return $query->whereNull('aprobacion_1')
                    ->where('status', MovementStatus::PENDING);
    }

    public function scopePendienteAprobacion2($query)
    {
        return $query->whereNotNull('aprobacion_1')
                    ->whereNull('aprobacion_2')
                    ->where('status', MovementStatus::VALID)
                    ->where('confirm_status', MovementStatus::PENDING);
    }

    public function scopeCompletamenteAprobado($query)
    {
        return $query->whereNotNull('aprobacion_1')
                    ->whereNotNull('aprobacion_2')
                    ->where('confirm_status', MovementStatus::VALID);
    }
}
