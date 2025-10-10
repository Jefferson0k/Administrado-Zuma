<?php

namespace App\Models;

use App\Helpers\MoneyConverter;
use App\Traits\ConvertsPercent;
use DateTime;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use IntlDateFormatter;
use Money\Money;

class Invoice extends Model implements AuditableContract{
    use HasFactory, HasUlids, Auditable, ConvertsPercent, SoftDeletes;
    protected $fillable = [
        'invoice_code',
        'codigo',
        'currency',
        'amount',
        'financed_amount_by_garantia',
        'financed_amount',
        'paid_amount',
        'rate',
        'due_date',
        'estimated_pay_date',
        'status',
        'company_id',
        'loan_number',
        'invoice_number',
        'RUC_client',
        'type',
        'statusPago',
        'condicion_oportunidad',

        // --- Aprobaciones ---
        'approval1_status',
        'approval1_by',
        'approval1_comment',
        'approval1_at',

        'approval2_status',
        'approval2_by',
        'approval2_comment',
        'approval2_at',

        // --- Auditoría ---
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    protected $casts = [
        'approval1_at' => 'datetime',
        'approval2_at' => 'datetime',
        'estimated_pay_date' => 'date',
        'created_at' => 'datetime',
        
    ];


    public $timestamps = true;

    protected static function boot(){
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::ulid()->toBase32();
            }

            if (empty($model->invoice_code)) {
                $model->invoice_code = Str::uuid()->toString();
            }
        });
    }
    public function approvals(){
        return $this->hasMany(InvoiceApproval::class);
    }
    public function creator(){
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updater(){
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function deleter(){
        return $this->belongsTo(User::class, 'deleted_by');
    }
    public function company(){
        return $this->belongsTo(Company::class);
    }
    public function investments(){
        return $this->hasMany(Investment::class);
    }
    public function payments(){
        return $this->hasMany(Payment::class);
    }
    public function isDaStandby(): bool {
        return $this->status === 'daStandby';
    }
    public function aprovacionuseruno(){
        return $this->belongsTo(User::class, 'approval1_by');
    }
    public function aprovacionuserdos(){
        return $this->belongsTo(User::class, 'approval2_by');
    }
    public function createdBy(){
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updatedBy(){
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function deletedBy(){
        return $this->belongsTo(User::class, 'deleted_by');
    }
    public function getEstimatedPayDateFormattedAttribute(){
        $date = new DateTime($this->attributes['estimated_pay_date']);
        $formatter = new IntlDateFormatter(
        'es_PE',
        IntlDateFormatter::MEDIUM,
        IntlDateFormatter::NONE
        );
        return $formatter->format($date);
    }
    public function getAmountAttribute(): ?string {
        if (empty($this->attributes['amount']) || empty($this->attributes['currency'])) {
            return null;
        }

        return MoneyConverter::fromSubunitToDecimal(
            $this->attributes['amount'],
            $this->attributes['currency']
        );
    }
    public function getFinancedAmountAttribute(): string{
        return MoneyConverter::fromSubunitToDecimal(
        $this->attributes['financed_amount'],
        $this->attributes['currency']
        );
    }
    public function getFinancedAmountByGarantiaAttribute(): string  {
        return MoneyConverter::fromSubunitToDecimal(
        $this->attributes['financed_amount_by_garantia'],
        $this->attributes['currency']
        );
    }
    public function getPaidAmountAttribute(): string{
        return MoneyConverter::fromSubunitToDecimal(
        $this->attributes['paid_amount'],
        $this->attributes['currency']
        );
    }
    public function getAvailablePaidAmount(): Money{
        $amountMoney = MoneyConverter::fromDecimal(
        $this->amount,
        $this->attributes['currency']
        );
        $paidAmountMoney = MoneyConverter::fromDecimal(
        $this->paid_amount,
        $this->attributes['currency']
        );
        return $amountMoney->subtract($paidAmountMoney);
    }
    public function containPartialPayments(): bool{
        return $this->payments()->where('pay_type', 'partial')->exists();
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
    public function setFinancedAmountAttribute(float | Money $value): void{
        if (!isset($this->attributes['currency'])) {
        throw new \RuntimeException('Currency must be set before financed amount');
        }
        if ($value instanceof Money) {
        $this->attributes['financed_amount'] = $value->getAmount();
        } else {
        $this->attributes['financed_amount'] = MoneyConverter::fromDecimal(
            $value,
            $this->attributes['currency']
        )->getAmount();
        }
    }
    public function setFinancedAmountByGarantiaAttribute(float | Money $value): void{
        if (!isset($this->attributes['currency'])) {
        throw new \RuntimeException('Currency must be set before financed amount by garantia');
        }
        if ($value instanceof Money) {
        $this->attributes['financed_amount_by_garantia'] = $value->getAmount();
        } else {
        $this->attributes['financed_amount_by_garantia'] = MoneyConverter::fromDecimal(
            $value,
            $this->attributes['currency']
        )->getAmount();
        }
    }
    public function setPaidAmountAttribute(float | Money $value): void{
        if (!isset($this->attributes['currency'])) {
        throw new \RuntimeException('Currency must be set before paid amount');
        }
        if ($value instanceof Money) {
        $this->attributes['paid_amount'] = $value->getAmount();
        } else {
        $this->attributes['paid_amount'] = MoneyConverter::fromDecimal(
            $value,
            $this->attributes['currency']
        )->getAmount();
        }
    }
    public function getInvestors(): array{
        $ids = $this->investments->pluck('investor_id');
        $investors = Investor::whereIn('id', $ids)->get();
        return $investors->toArray();
    }
    public function anularFactura(int $userId, string $comment = null): bool{
        if (in_array($this->status, ['paid', 'annulled'])) {
            return false;
        }
        $this->type = 'annulled';
        $this->approval2_status = 'rejected';
        $this->approval2_by = $userId;
        $this->approval2_comment = $comment;
        $this->approval2_at = now();
        if ($this->codigo && !str_ends_with($this->codigo, '- ANULADA')) {
            $this->codigo .= ' - ANULADA';
        }
        if ($this->invoice_number && !str_ends_with($this->invoice_number, '- ANULADA')) {
            $this->invoice_number .= ' - ANULADA';
        }
        return $this->save();
    }


   

}
