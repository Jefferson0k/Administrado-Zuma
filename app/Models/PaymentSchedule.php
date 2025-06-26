<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PaymentSchedule extends Model{
    protected $fillable = [
        'investment_id', 'nro_cuota', 'fecha_vencimiento',
        'saldo_inicial', 'capital', 'interes', 'cuota_neta',
        'igv', 'cuota_total', 'saldo_final'
    ];
    public function investment(){
        return $this->belongsTo(Investment::class);
    }
}
