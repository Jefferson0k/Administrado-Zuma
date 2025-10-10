<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SolicitudBid extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'solicitud_bids';

    protected $fillable = [
        'solicitud_id',
        'investor_id',
        'ganador_id',
        'estado',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /* 🔹 Relaciones */
    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }

    public function investor()
    {
        return $this->belongsTo(Investor::class, 'investor_id');
    }

    public function ganador()
    {
        return $this->belongsTo(Investor::class, 'ganador_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function eliminador()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
