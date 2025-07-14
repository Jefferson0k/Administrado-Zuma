<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model{
    use HasFactory;
    protected $fillable = [
        'dni',
        'nombre',
        'apellidos',
        'estado'
    ];
    public function loanDetails(){
        return $this->hasMany(PropertyLoanDetail::class);
    }
}
