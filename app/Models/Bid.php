<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bid extends Model{
    use HasFactory;
    protected $table = 'bids';
    protected $fillable = [
        'auction_id',
        'solicitud_bid_id',
        'investors_id',
        'type',
    ];
    // -------------------------
    // 🔗 Relaciones
    // -------------------------
    public function investor(){
        return $this->belongsTo(Investor::class, 'investors_id', 'id');
    }
    public function subasta(){
        return $this->belongsTo(Auction::class, 'auction_id', 'id');
    }
    public function solicitudBid(){
        return $this->belongsTo(SolicitudBid::class, 'solicitud_bid_id', 'id');
    }
}
