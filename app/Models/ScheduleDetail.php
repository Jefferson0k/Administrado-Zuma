<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleDetail extends Model{
    use HasFactory;
    protected $fillable = [
        'investment_schedule_id',
        'month',
        'schedule',
        '',
        'payment_date',
        'days',
        'base_amount',
        'base_interest',
        'second_category_tax',
        'net_interest',
        'capital_return',
        'capital_balance',
        'total_payment',
    ];
    public function investmentSchedule(){
        return $this->belongsTo(InvestmentSchedule::class);
    }
}
