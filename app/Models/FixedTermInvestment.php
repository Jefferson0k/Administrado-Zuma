<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedTermInvestment extends Model{
    use HasFactory;
    protected $fillable = [
        'investor_id',
        'fixed_term_rate_id',
        'term_plan_id',
        'payment_frequency_id',
        'amount',
        'status',
    ];
    public function investor(){
        return $this->belongsTo(Investor::class);
    }
    public function rate(){
        return $this->belongsTo(FixedTermRate::class, 'fixed_term_rate_id');
    }
    public function frequency(){
        return $this->belongsTo(PaymentFrequency::class, 'payment_frequency_id');
    }
    public function termPlan(){
        return $this->belongsTo(TermPlan::class, 'term_plan_id');
    }
    public function schedules(){
        return $this->hasMany(FixedTermSchedule::class);
    }
    public function checkAndFinalizeIfNeeded() {
        $remaining = $this->schedules()->where('status', '!=', 'pagado')->count();
        if ($remaining === 0) {
            $this->update(['status' => 'finalizado']);
        }
    }
}
