<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Money\Money;
use Money\Currency;

class PropertyInvestor extends Model implements AuditableContract{
    use HasFactory, SoftDeletes, Auditable;
    protected $table = 'property_investors';
    protected $fillable = [
        'solicitud_id',
        'investor_id',
        'config_id',
        'amount',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    
    // -------------------------
    // Relaciones
    // -------------------------
    public function solicitud(){
        return $this->belongsTo(Solicitud::class);
    }
    public function investor(){
        return $this->belongsTo(Investor::class);
    }
    public function paymentSchedules(){
        return $this->hasMany(PaymentSchedule::class);
    }
    public function loanDetails(){
        return $this->hasMany(PropertyLoanDetail::class, 'id_property_investor');
    }
    public function configuracion(){
        return $this->belongsTo(PropertyConfiguracion::class, 'config_id');
    }
    // -------------------------
    // Accessors & Mutators
    // -------------------------
    /**
     * Convierte el amount (int en centavos) a objeto Money.
     */
    public function getAmountAttribute($value){
        if ($value === null) {
            return null;
        }
        // Asumimos que la moneda por defecto es PEN (puedes cambiarlo a USD u otro)
        return new Money($value, new Currency('PEN'));
    }
    /**
     * Guarda el amount como integer en centavos.
     * Se admite que venga como Money o como decimal/string.
     */
    public function setAmountAttribute($value){
        if ($value instanceof Money) {
            $this->attributes['amount'] = $value->getAmount();
        } elseif ($value === null || $value === '') {
            $this->attributes['amount'] = null;
        } else {
            $this->attributes['amount'] = (int) round(floatval($value) * 100);
        }
    }
}
