<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class PagoTasa extends Model{
    use HasFactory, HasUlids;
    protected $table = 'pagos_tasas';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'mes',
        'monto',
        'moneda',
        'id_fixed_term_schedule',
        'id_inversionista',
    ];
    public function fixedTermSchedule() {
        return $this->belongsTo(FixedTermSchedule::class, 'id_fixed_term_schedule');
    }
    public function inversionista() {
        return $this->belongsTo(Investor::class, 'id_inversionista');
    }
}
