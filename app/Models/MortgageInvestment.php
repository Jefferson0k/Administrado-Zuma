<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MortgageInvestment extends Model
{
    use HasFactory;

    protected $fillable = [
        'investor_id',
        'property_id',
        'deadline_id',
        'product_id',
        'product_account_id',
        'monthly_amount',
        'total_amount',
        'currency',
        'status',
    ];

    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function deadline()
    {
        return $this->belongsTo(Deadlines::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function account()
    {
        return $this->belongsTo(ProductAccount::class, 'product_account_id');
    }

    public function schedules()
    {
        return $this->hasMany(MortgagePaymentSchedule::class);
    }

    public function payments()
    {
        return $this->hasMany(MortgagePayment::class);
    }
}
