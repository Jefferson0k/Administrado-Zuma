<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoInmueble extends Model
{
    use HasFactory;

    protected $table = 'tipo_inmueble';
    protected $primaryKey = 'id_tipo_inmueble';

    protected $fillable = [
        'nombre_tipo_inmueble',
        'orden_tipo_inmueble',
    ];
}
