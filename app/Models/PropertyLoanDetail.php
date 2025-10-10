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
        'empresa_tasadora',
        'motivo_prestamo',
        'descripcion_financiamiento',
        'monto_tasacion',
        'porcentaje_prestamo',
        'monto_invertir',
        'monto_prestamo',
        'solicitud_prestamo_para',
        'estado_conclusion',
        'approval1_status',
        'approval1_by',
        'approval1_comment',
        'approval1_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    // -------------------------
    // Relaciones
    // -------------------------

    public function investor()
    {
        return $this->belongsTo(Investor::class, 'investor_id');
    }

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }

    public function configuracion()
    {
        return $this->belongsTo(PropertyConfiguracion::class, 'config_id', 'id');
    }

    public function detalleInversionistaHipoteca()
    {
        return $this->hasOne(DetalleInversionistaHipoteca::class, 'configuracion_id', 'config_id');
    }

    // 🧑‍💼 Relación con el usuario que aprueba
    public function approval1User()
    {
        return $this->belongsTo(User::class, 'approval1_by');
    }

    // 🧑‍💻 Usuarios que crean, actualizan o eliminan registros
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedByUser()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
