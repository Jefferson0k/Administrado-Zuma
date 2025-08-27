<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyFinance extends Model
{
    use HasFactory;
    
    protected $table = 'company_finances';
    
    protected $fillable = [
        'company_id',
        // Legacy fields (keep for backwards compatibility)
        'facturas_financiadas',
        'monto_total_financiado',
        'pagadas',
        'pendientes',
        'plazo_promedio_pago',
        
        // New currency-specific fields for PEN
        'sales_volume_pen',
        'facturas_financiadas_pen',
        'monto_total_financiado_pen',
        'pagadas_pen',
        'pendientes_pen',
        'plazo_promedio_pago_pen',
        
        // New currency-specific fields for USD
        'sales_volume_usd',
        'facturas_financiadas_usd',
        'monto_total_financiado_usd',
        'pagadas_usd',
        'pendientes_usd',
        'plazo_promedio_pago_usd',
    ];

    protected $casts = [
        'sales_volume_pen' => 'decimal:2',
        'monto_total_financiado_pen' => 'decimal:2',
        'sales_volume_usd' => 'decimal:2',
        'monto_total_financiado_usd' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    /**
     * Get total sales volume across all currencies
     */
    public function getTotalSalesVolumeAttribute()
    {
        $total = 0;
        
        if ($this->sales_volume_pen) {
            $total += $this->sales_volume_pen;
        }
        
        if ($this->sales_volume_usd) {
            // Convert USD to PEN using an exchange rate (you might want to make this configurable)
            $exchangeRate = 3.75; // Example rate - you should get this from a service
            $total += $this->sales_volume_usd * $exchangeRate;
        }
        
        return $total;
    }

    /**
     * Get total facturas financiadas across all currencies
     */
    public function getTotalFacturasFinanciadasAttribute()
    {
        return ($this->facturas_financiadas_pen ?? 0) + ($this->facturas_financiadas_usd ?? 0);
    }

    /**
     * Get total monto financiado in PEN equivalent
     */
    public function getTotalMontoFinanciadoAttribute()
    {
        $total = $this->monto_total_financiado_pen ?? 0;
        
        if ($this->monto_total_financiado_usd) {
            $exchangeRate = 3.75; // Example rate
            $total += $this->monto_total_financiado_usd * $exchangeRate;
        }
        
        return $total;
    }

    /**
     * Get total pagadas across all currencies
     */
    public function getTotalPagadasAttribute()
    {
        return ($this->pagadas_pen ?? 0) + ($this->pagadas_usd ?? 0);
    }

    /**
     * Get total pendientes across all currencies
     */
    public function getTotalPendientesAttribute()
    {
        return ($this->pendientes_pen ?? 0) + ($this->pendientes_usd ?? 0);
    }

    /**
     * Get average plazo promedio weighted by currency
     */
    public function getAveragePlazoPromedioAttribute()
    {
        $totalFacturas = $this->getTotalFacturasFinanciadasAttribute();
        
        if ($totalFacturas == 0) {
            return 0;
        }
        
        $weightedSum = 0;
        
        if ($this->facturas_financiadas_pen > 0) {
            $weightedSum += ($this->plazo_promedio_pago_pen ?? 0) * $this->facturas_financiadas_pen;
        }
        
        if ($this->facturas_financiadas_usd > 0) {
            $weightedSum += ($this->plazo_promedio_pago_usd ?? 0) * $this->facturas_financiadas_usd;
        }
        
        return round($weightedSum / $totalFacturas, 2);
    }
}