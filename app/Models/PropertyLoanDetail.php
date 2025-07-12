<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PropertyLoanDetail extends Model{
    use HasFactory;
    protected $fillable = [
        'property_id',
        'customer_id',
        'ocupacion_profesion',
        'motivo_prestamo',
        'descripcion_financiamiento',
        'solicitud_prestamo_para',
        'garantia',
        'perfil_riesgo',
    ];
    public function customer(){
        return $this->belongsTo(Customer::class);
    }
    public function property(){
        return $this->belongsTo(Property::class);
    }
}
