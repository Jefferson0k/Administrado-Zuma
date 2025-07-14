<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmountRange extends Model{
    use HasFactory;
    protected $table = 'amount_ranges';
    protected $fillable = [
        'corporate_entity_id', 'desde', 'hasta', 'moneda',
    ];
    public function corporateEntity(){
        return $this->belongsTo(CorporateEntity::class);
    }
    public function fixedTermRates(){
        return $this->hasMany(FixedTermRate::class);
    }
}
