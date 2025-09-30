<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class PropertyConfiguracion extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'property_configuracions';

    protected $fillable = [
        'property_id',
        'deadlines_id',
        'tea',
        'tem',
        'tipo_cronograma',
        'riesgo',
        'estado',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    // -------------------------
    // Relaciones
    // -------------------------
    public function plazo(){
        return $this->belongsTo(Deadlines::class, 'deadlines_id');
    }

    public function property(){
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function subasta(){
        return $this->property->subasta();
    }

    public function propertyInvestor(){
        return $this->hasOne(PropertyInvestor::class, 'config_id');
    }

    // -------------------------
    // Mutators & Accessors
    // -------------------------

    // Getter TEA → devuelve decimal con 2 decimales
    public function getTeaAttribute($value)
    {
        return $value === null ? null : number_format($value / 100, 2, '.', '');
    }

    // Getter TEM → devuelve decimal con 2 decimales
    public function getTemAttribute($value)
    {
        return $value === null ? null : number_format($value / 100, 2, '.', '');
    }

    // Setter TEA → guarda como entero multiplicado por 100
    public function setTeaAttribute($value)
    {
        $this->attributes['tea'] = ($value === null || $value === '')
            ? null
            : (int) round(floatval($value) * 100);
    }

    // Setter TEM → guarda como entero multiplicado por 100
    public function setTemAttribute($value)
    {
        $this->attributes['tem'] = ($value === null || $value === '')
            ? null
            : (int) round(floatval($value) * 100);
    }

    public function detalleInversionistaHipoteca()
    {
        return $this->hasOne(DetalleInversionistaHipoteca::class, 'configuracion_id');
    }

}
