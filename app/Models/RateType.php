<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateType extends Model{
    use HasFactory;
    protected $table = 'rate_types';
    protected $fillable = [
        'nombre', 'descripcion',
    ];
    public function fixedTermRates(){
        return $this->hasMany(FixedTermRate::class);
    }
}
