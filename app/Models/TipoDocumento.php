<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoDocumento extends Model
{
    use HasFactory;

    protected $table = 'tipo_documento';
    protected $primaryKey = 'id_tipo_documento';
    public $timestamps = true;

    protected $fillable = [
        'nombre_tipo_documento',
        'orden_tipo_documento',
    ];

    public function investors() {
    return $this->hasMany(Investor::class, 'tipo_documento_id', 'id_tipo_documento');
}

}
