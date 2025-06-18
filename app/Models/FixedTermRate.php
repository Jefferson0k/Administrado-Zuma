<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedTermRate extends Model{
    use HasFactory;
    protected $table = 'fixed_term_rates';
    protected $fillable = [
        'corporate_entity_id',
        'amount_range_id',
        'term_plan_id',
        'rate_type_id',
        'valor',
        'estado',   
    ];
    public function corporateEntity(){
        return $this->belongsTo(CorporateEntity::class);
    }
    public function amountRange(){
        return $this->belongsTo(AmountRange::class);
    }
    public function termPlan(){
        return $this->belongsTo(TermPlan::class);
    }
    public function rateType(){
        return $this->belongsTo(RateType::class);
    }
}
