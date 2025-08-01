<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyInvestor extends Model{
    use HasFactory;
    protected $table = 'property_investors';
    protected $fillable = [
        'property_id',
        'investor_id',
        'config_id',
        'amount',
        'status',
    ];
    public function property(){
        return $this->belongsTo(Property::class);
    }
    public function investor(){
        return $this->belongsTo(Investor::class);
    }
    public function paymentSchedules(){
        return $this->hasMany(PaymentSchedule::class);
    }
    public function loanDetails(){
        return $this->hasMany(PropertyLoanDetail::class, 'id_property_investor');
    }
    public function configuracion(){
        return $this->belongsTo(PropertyConfiguracion::class, 'config_id');
    }
}
