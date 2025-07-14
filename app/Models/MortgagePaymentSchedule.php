<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MortgagePaymentSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'mortgage_investment_id',
        'property_id',
        'installment_number',
        'due_date',
        'amount',
        'paid',
    ];

    public function investment(){
        return $this->belongsTo(MortgageInvestment::class, 'mortgage_investment_id');
    }
    public function property(){
        return $this->belongsTo(Property::class);
    }

}
