<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TermPlan extends Model{
    use HasFactory;
    protected $table = 'term_plans';
    protected $fillable = [
        'nombre', 'dias_minimos', 'dias_maximos',
    ];
    public function fixedTermRates(){
        return $this->hasMany(FixedTermRate::class);
    }
}
