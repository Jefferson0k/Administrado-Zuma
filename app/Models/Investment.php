<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Investment extends Model{
    use HasFactory;
    protected $table = 'investments';
     protected $fillable = [
        'customer_id', 'property_id', 'term_id',
        'monto_invertido', 'fecha_inversion', 'estado'
    ];
    public function customer(){
        return $this->belongsTo(Customer::class);
    }
    public function property(){
        return $this->belongsTo(Property::class);
    }
    public function paymentSchedules(){
        return $this->hasMany(PaymentSchedule::class);
    }
}