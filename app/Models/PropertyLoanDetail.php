<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class PropertyLoanDetail extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'property_loan_details';

    protected $fillable = [
        'solicitud_id',
        'config_id',
        'investor_id',
        // 'ocupacion_profesion',
        'empresa_tasadora',
        'motivo_prestamo',
        'descripcion_financiamiento',
        'monto_tasacion',
        'porcentaje_prestamo',
        'monto_invertir',
        'monto_prestamo',
        'solicitud_prestamo_para',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    // -------------------------
    // Relaciones
    // -------------------------
    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }
    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id', 'id');
    }

    public function configuracion()
    {
        return $this->belongsTo(PropertyConfiguracion::class, 'config_id');
    }
public function detalleInversionistaHipoteca()
{
    return $this->hasOne(DetalleInversionistaHipoteca::class, 'configuracion_id', 'config_id');
}


}
