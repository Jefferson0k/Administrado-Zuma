<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Money\Money;
use Money\Currency as MoneyCurrency;

class Solicitud extends Model
{
    use HasFactory;

    protected $table = 'solicitudes';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'codigo',
        'investor_id',
        'valor_general',
        'valor_requerido',
        'type',
        'currency_id',
        'estado',
        'config_total',
        'fuente_ingreso',
        'profesion_ocupacion',
        'ingreso_promedio',
        'approval1_status',
        'approval1_by',
        'approval1_comment',
        'approval1_at'
    ];

    // -------------------------
    // Relaciones
    // -------------------------
    public function investor()
    {
        return $this->belongsTo(Investor::class, 'investor_id', 'id');
    }

    public function properties()
    {
        return $this->hasMany(Property::class, 'solicitud_id', 'id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id', 'id');
    }

    // -------------------------
    // 💰 Accessors para valores
    // -------------------------
    public function getValorGeneralAttribute($value)
    {
        return $this->asMoney($value, 'valor_general');
    }

    public function getValorRequeridoAttribute($value)
    {
        return $this->asMoney($value, 'valor_requerido');
    }

    private function asMoney($value, string $field): Money
    {
        try {
            $currencyCode = $this->currency?->codigo ?? 'PEN';
            return new Money((int) $value, new MoneyCurrency($currencyCode));
        } catch (Exception $e) {
            Log::warning("Error creando Money en Solicitud para {$field}", [
                'solicitud_id' => $this->id,
                'value' => $value,
                'currency_id' => $this->currency_id,
                'error' => $e->getMessage()
            ]);
            return new Money((int) $value, new MoneyCurrency('PEN'));
        }
    }
    public function configuraciones()
    {
        return $this->hasMany(PropertyConfiguracion::class, 'solicitud_id');
    }

    public function propertyInvestors()
    {
        return $this->hasMany(PropertyInvestor::class, 'solicitud_id', 'id');
    }
    public function setValorGeneralAttribute($value)
    {
        $this->attributes['valor_general'] = !$this->exists
            ? (int) round($value * 100)
            : (int) $value;
    }

    public function setValorRequeridoAttribute($value)
    {
        $this->attributes['valor_requerido'] = !$this->exists
            ? (int) round($value * 100)
            : (int) $value;
    }
    public function subasta()
    {
        return $this->hasOne(Auction::class, 'solicitud_id');
    }
    public function configuracionSubasta(){
        return $this->hasOne(PropertyConfiguracion::class, 'solicitud_id')
            ->where('estado', 2);
    }

    public function configuracionActiva()
    {
        return $this->hasOne(PropertyConfiguracion::class, 'solicitud_id')
            ->where('estado', 1);
    }
    public function approvals()
    {
        return $this->hasMany(SolicitudApproval::class, 'solicitud_id');
    }
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approval1_by', 'id');
    }

}
