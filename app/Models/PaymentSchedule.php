<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSchedule extends Model{
    use HasFactory;
    protected $fillable = [
        'property_investor_id',
        'cuota',
        'vencimiento',
        'saldo_inicial',
        'capital',
        'intereses',
        'cuota_neta',
        'igv',
        'total_cuota',
        'saldo_final',
        'estado',
    ];
    public function propertyInvestor(){
        return $this->belongsTo(PropertyInvestor::class);
    }
}
