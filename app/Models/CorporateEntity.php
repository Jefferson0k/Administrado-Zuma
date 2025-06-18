<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorporateEntity extends Model{
    use HasFactory;
    protected $table = 'corporate_entities';
    protected $fillable = [
        'nombre', 'ruc', 'direccion', 'telefono', 'email', 'tipo_entidad', 'estado',
    ];
    public function amountRanges(){
        return $this->hasMany(AmountRange::class);
    }
    public function fixedTermRates(){
        return $this->hasMany(FixedTermRate::class);
    }
}
