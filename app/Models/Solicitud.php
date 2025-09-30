<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    use HasFactory;

    protected $table = 'solicitud';
    protected $primaryKey = 'id_solicitud';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'numero_solicitud',
        'id_investors',
        'fecha_solicitud',
        'type'
    ];

    // Relación con Investor
    public function investor()
    {
        return $this->belongsTo(Investor::class, 'id_investors', 'id');
    }

    // Relación con Inmuebles (cada solicitud puede tener varios inmuebles)
    public function properties()
    {
        return $this->hasMany(Property::class, 'id_solicitud', 'id_solicitud');
    }
}
