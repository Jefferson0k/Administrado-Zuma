<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Deadlines extends Model{
    use HasFactory;
    protected $table = 'deadlines';
    protected $fillable = [
        'nombre',
        'duracion_meses'
    ];
    public function properties(){
        return $this->hasMany(Property::class);
    }
}
