<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyFinance extends Model implements AuditableContract{
    use HasFactory, Auditable, SoftDeletes;
    protected $table = 'company_finances';
    protected $fillable = [
        'company_id',
        'facturas_financiadas',
        'monto_total_financiado',
        'pagadas',
        'pendientes',
        'plazo_promedio_pago',

        'sales_volume_pen',
        'facturas_financiadas_pen',
        'monto_total_financiado_pen',
        'pagadas_pen',
        'pendientes_pen',
        'plazo_promedio_pago_pen',

        'sales_volume_usd',
        'facturas_financiadas_usd',
        'monto_total_financiado_usd',
        'pagadas_usd',
        'pendientes_usd',
        'plazo_promedio_pago_usd',

        'created_by',
        'updated_by',
    ];
    protected $casts = [
        'sales_volume_pen' => 'decimal:2',
        'monto_total_financiado_pen' => 'decimal:2',
        'sales_volume_usd' => 'decimal:2',
        'monto_total_financiado_usd' => 'decimal:2',
    ];

    /**
     * Relación con la compañía
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    /**
     * Total sales volume en PEN (conversión de USD)
     */
    public function getTotalSalesVolumeAttribute()
    {
        $total = $this->sales_volume_pen ?? 0;
        if ($this->sales_volume_usd) {
            $exchangeRate = 3.75; // ejemplo, configurable
            $total += $this->sales_volume_usd * $exchangeRate;
        }
        return $total;
    }

    /**
     * Total facturas financiadas
     */
    public function getTotalFacturasFinanciadasAttribute()
    {
        return ($this->facturas_financiadas_pen ?? 0) + ($this->facturas_financiadas_usd ?? 0);
    }

    /**
     * Total monto financiado en PEN equivalente
     */
    public function getTotalMontoFinanciadoAttribute()
    {
        $total = $this->monto_total_financiado_pen ?? 0;
        if ($this->monto_total_financiado_usd) {
            $exchangeRate = 3.75;
            $total += $this->monto_total_financiado_usd * $exchangeRate;
        }
        return $total;
    }

    /**
     * Total pagadas
     */
    public function getTotalPagadasAttribute()
    {
        return ($this->pagadas_pen ?? 0) + ($this->pagadas_usd ?? 0);
    }

    /**
     * Total pendientes
     */
    public function getTotalPendientesAttribute()
    {
        return ($this->pendientes_pen ?? 0) + ($this->pendientes_usd ?? 0);
    }

    /**
     * Promedio ponderado del plazo de pago
     */
    public function getAveragePlazoPromedioAttribute()
    {
        $totalFacturas = $this->getTotalFacturasFinanciadasAttribute();
        if ($totalFacturas == 0) {
            return 0;
        }

        $weightedSum = 0;
        if (($this->facturas_financiadas_pen ?? 0) > 0) {
            $weightedSum += ($this->plazo_promedio_pago_pen ?? 0) * $this->facturas_financiadas_pen;
        }
        if (($this->facturas_financiadas_usd ?? 0) > 0) {
            $weightedSum += ($this->plazo_promedio_pago_usd ?? 0) * $this->facturas_financiadas_usd;
        }

        return round($weightedSum / $totalFacturas, 2);
    }
}
