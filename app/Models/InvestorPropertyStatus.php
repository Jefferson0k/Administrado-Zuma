<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvestorPropertyStatus extends Model{
    use HasFactory;
    protected $table = 'investor_property_status';

    protected $fillable = [
        'investor_id',
        'property_id',
        'monto',
        'estado',
    ];
    public function investor(){
        return $this->belongsTo(Investor::class);
    }
    public function property(){
        return $this->belongsTo(Property::class);
    }
}
