<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Investment extends Model{
    use HasFactory;
    protected $table = 'investments';
    protected $fillable = ['customer_id', 'property_id', 'monto_invertido', 'fecha_inversion'];
    public function customer(){
        return $this->belongsTo(Customer::class , 'customer_id');
    }
    public function propiedad(){
        return $this->belongsTo(Property::class, 'property_id');
    }
    public function subasta(){
        return $this->hasOne(Auction::class, 'property_id', 'property_id');
    }
}