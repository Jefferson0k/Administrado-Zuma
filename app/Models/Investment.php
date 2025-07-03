<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Investment extends Model{
    use HasFactory;
    protected $table = 'investments';
     protected $fillable = [
        'investor_id', 'property_id', 'term_id',
        'monto_invertido', 'fecha_inversion', 'estado'
    ];
    public function investor(){
        return $this->belongsTo(Investor::class);
    }
    public function property(){
        return $this->belongsTo(Property::class);
    }
}