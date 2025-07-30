<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvestorCode extends Model{
    use HasFactory;
    protected $table = 'investor_codes';
    protected $fillable = [
        'codigo',
        'usado',
        'investor_id',
    ];
    protected $casts = [
        'usado' => 'boolean',
    ];
    public function investor(){
        return $this->belongsTo(Investor::class);
    }
}
