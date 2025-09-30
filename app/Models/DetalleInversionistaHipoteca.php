<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleInversionistaHipoteca extends Model
{
    use HasFactory;

    protected $table = 'detalle_inversionista_hipoteca';
    protected $primaryKey = 'id_detalle_inversionista_hipoteca';

    protected $fillable = [
        'fuente_ingreso',
        'profesion_ocupacion',
        'ingreso_promedio',
        'investor_id',
        'configuracion_id'
    ];

    // Relación con Investor
    public function investor()
    {
        return $this->belongsTo(Investor::class, 'investor_id');
    }

    public function configuracion()
    {
        return $this->belongsTo(PropertyConfiguracion::class, 'configuracion_id');
    }
}