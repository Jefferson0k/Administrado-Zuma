<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentSchedule extends Model{
    use HasFactory;
    protected $fillable = [
        'amount',
        'payment_frequency_id',
        'rate_id',
        'start_date',
        'tax_rate',
        'investor_id',
    ];
    public function investor(){
        return $this->belongsTo(Investor::class);
    }
    public function details(){
        return $this->hasMany(ScheduleDetail::class);
    }
}
