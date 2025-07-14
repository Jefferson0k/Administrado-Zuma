<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedTermSchedule extends Model{
    use HasFactory;
    protected $fillable = [
        'fixed_term_investment_id',
        'month',
        'payment_date',
        'days',
        'base_amount',
        'interest_amount',
        'second_category_tax',
        'interest_to_deposit',
        'capital_return',
        'capital_balance',
        'total_to_deposit',
        'status',
    ];
    public function investment(){
        return $this->belongsTo(FixedTermInvestment::class, 'fixed_term_investment_id');
    }
}
