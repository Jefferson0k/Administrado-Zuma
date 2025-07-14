<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MortgagePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'mortgage_investment_id',
        'paid_at',
        'amount',
        'method',
        'referencia',
    ];

    public function investment()
    {
        return $this->belongsTo(MortgageInvestment::class, 'mortgage_investment_id');
    }
}
