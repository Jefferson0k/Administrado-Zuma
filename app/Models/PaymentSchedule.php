<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSchedule extends Model{
    use HasFactory;
    protected $fillable = [
        'property_investor_id',
        'cuota',
        'vencimiento',
        'saldo_inicial',
        'capital',
        'intereses',
        'cuota_neta',
        'igv',
        'total_cuota',
        'saldo_final',
        'estado',
    ];
    protected $casts = [
        'vencimiento' => 'date:Y-m-d',
        'cuota' => 'integer',
    ];

    public function propertyInvestor()
    {
        return $this->belongsTo(PropertyInvestor::class);
    }

    /* ============================
     * Helpers de conversión MEJORADOS para montos grandes
     * ============================ */
    private function toInt($value): int
    {
        if ($value === null || $value === '') return 0;
        
        // Si es string, limpiamos cualquier formato
        if (is_string($value)) {
            // Remover comas, espacios y otros caracteres de formato
            $cleaned = preg_replace('/[^0-9.-]/', '', $value);
            $value = $cleaned;
        }
        
        // USAR bcmath para evitar pérdida de precisión con números grandes
        bcscale(2);
        $valueStr = (string) $value;
        $centavos = bcmul($valueStr, '100', 0); // 0 decimales para resultado entero
        
        return (int) $centavos;
    }

    private function toDecimal(?int $value): float
    {
        if ($value === null) return 0.0;
        
        // USAR bcmath para división exacta
        bcscale(2);
        $result = bcdiv((string) $value, '100', 2);
        
        return (float) $result;
    }

    /* ============================
     * Mutators (setters) -> guardan en centavos
     * ============================ */
    public function setSaldoInicialAttribute($value) 
    { 
        $this->attributes['saldo_inicial'] = $this->toInt($value); 
    }
    
    public function setCapitalAttribute($value)      
    { 
        $this->attributes['capital'] = $this->toInt($value); 
    }
    
    public function setInteresesAttribute($value)    
    { 
        $this->attributes['intereses'] = $this->toInt($value); 
    }
    
    public function setCuotaNetaAttribute($value)    
    { 
        $this->attributes['cuota_neta'] = $this->toInt($value); 
    }
    
    public function setIgvAttribute($value)          
    { 
        $this->attributes['igv'] = $this->toInt($value); 
    }
    
    public function setTotalCuotaAttribute($value)   
    { 
        $this->attributes['total_cuota'] = $this->toInt($value); 
    }
    
    public function setSaldoFinalAttribute($value)   
    { 
        $this->attributes['saldo_final'] = $this->toInt($value); 
    }

    /* ============================
     * Accessors (getters) -> exponen decimales
     * ============================ */
    public function getSaldoInicialAttribute($value) 
    { 
        return $this->toDecimal($value); 
    }
    
    public function getCapitalAttribute($value)      
    { 
        return $this->toDecimal($value); 
    }
    
    public function getInteresesAttribute($value)    
    { 
        return $this->toDecimal($value); 
    }
    
    public function getCuotaNetaAttribute($value)    
    { 
        return $this->toDecimal($value); 
    }
    
    public function getIgvAttribute($value)          
    { 
        return $this->toDecimal($value); 
    }
    
    public function getTotalCuotaAttribute($value)   
    { 
        return $this->toDecimal($value); 
    }
    
    public function getSaldoFinalAttribute($value)   
    { 
        return $this->toDecimal($value); 
    }

    /* ============================
     * Métodos helper adicionales
     * ============================ */
    public function getRawCentavosAttribute($field)
    {
        return $this->attributes[$field] ?? 0;
    }

    public function getFormattedAmounts()
    {
        return [
            'saldo_inicial_formatted' => number_format($this->saldo_inicial, 2, '.', ','),
            'capital_formatted' => number_format($this->capital, 2, '.', ','),
            'intereses_formatted' => number_format($this->intereses, 2, '.', ','),
            'cuota_neta_formatted' => number_format($this->cuota_neta, 2, '.', ','),
            'igv_formatted' => number_format($this->igv, 2, '.', ','),
            'total_cuota_formatted' => number_format($this->total_cuota, 2, '.', ','),
            'saldo_final_formatted' => number_format($this->saldo_final, 2, '.', ','),
        ];
    }
}