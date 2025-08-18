<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PropertyConfiguracion extends Model{
    use HasFactory;
    protected $table = 'property_configuracions';
    protected $fillable = [
        'property_id',
        'deadlines_id',
        'tea',
        'tem',
        'tipo_cronograma',
        'riesgo',
        'estado'
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
    // Accessors & Mutators
    // -------------------------
    /**
     * Mutator: guarda TEA como decimal (ej: 0.1525 = 15.25%)a
     */
    public function getTeaAttribute($value)
    {
        if ($value === null) {
            return null;
        }
        
        // Asumiendo que guardas como: 20.00% -> 2000 (multiplicado por 100)
        // Ajusta el divisor según tu lógica de almacenamiento
        return number_format($value / 100, 2, '.', '');
    }

    /**
     * Convierte TEM de entero a porcentaje decimal
     * BD: 150 -> Frontend: 1.50
     */
    public function getTemAttribute($value)
    {
        if ($value === null) {
            return null;
        }
        
        // Asumiendo que guardas como: 1.50% -> 150 (multiplicado por 100)
        // Ajusta el divisor según tu lógica de almacenamiento
        return number_format($value / 100, 2, '.', '');
    }

    /**
     * Convierte TEA de porcentaje decimal a entero para BD
     * Frontend: 20.00 -> BD: 2000
     */
    public function setTeaAttribute($value)
    {
        if ($value === null || $value === '') {
            $this->attributes['tea'] = null;
            return;
        }
        
        // Multiplica por 100 para guardar como entero
        $this->attributes['tea'] = (int) round(floatval($value) * 100);
    }

    /**
     * Convierte TEM de porcentaje decimal a entero para BD
     * Frontend: 1.50 -> BD: 150
     */
    public function setTemAttribute($value)
    {
        if ($value === null || $value === '') {
            $this->attributes['tem'] = null;
            return;
        }
        
        // Multiplica por 100 para guardar como entero
        $this->attributes['tem'] = (int) round(floatval($value) * 100);
    }
}
