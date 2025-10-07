<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class Auction extends Model implements AuditableContract{
    use HasFactory, SoftDeletes, Auditable;
    protected $table = 'auctions';
    protected $fillable = [
        'solicitud_id', 'monto_inicial', 'dia_subasta',
        'hora_inicio', 'hora_fin', 'tiempo_finalizacion',
        'estado', 'ganador_id', 'created_by', 'updated_by', 'deleted_by'
    ];
    public function pujas() {
        return $this->hasMany(Bid::class);
    }
    public function ganador() {
        return $this->belongsTo(Investor::class, 'ganador_id');
    }
    public function solicitud(){
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }
}
