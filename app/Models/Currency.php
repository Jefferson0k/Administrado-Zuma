<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Currency extends Model{
    use HasFactory;
    protected $table = 'currencies';
    protected $fillable = [
        'nombre',
        'codigo',
        'simbolo',
    ];
    public function properties(){
        return $this->hasMany(Property::class);
    }
}
