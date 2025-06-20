<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bid extends Model{
    use HasFactory;
    protected $table = 'bids';
    protected $fillable = ['auction_id', 'customer_id', 'monto'];
    public function subasta() {
        return $this->belongsTo(Auction::class, 'auction_id');
    }
    public function customer(){
        return $this->belongsTo(Customer::class , 'customer_id');
    }
}
