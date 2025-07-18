<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Balance extends Model{
    use HasFactory, HasUlids;
    protected $table = 'balances';
    protected $fillable = [
        'amount',
        'invested_amount',
        'expected_amount',
        'currency',
        'investor_id',
    ];
    public function investor(){
        return $this->belongsTo(Investor::class);
    }
}
