<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Balance extends Model{
    use HasFactory;
    protected $table = 'balances';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

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
