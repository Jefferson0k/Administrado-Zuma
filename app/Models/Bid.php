<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bid extends Model
{
    use HasFactory;

    protected $table = 'bids';

    protected $fillable = ['auction_id', 'investors_id', 'monto'];

    public function subasta()
    {
        return $this->belongsTo(Auction::class, 'auction_id');
    }

    public function investor()
    {
        return $this->belongsTo(Investor::class, 'investors_id');
    }
}
