<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class PropertyReservation extends Model{
    use HasFactory;
    protected $table = 'property_reservations';
    protected $fillable = [
        'investor_id',
        'property_id',
        'config_id',
        'amount',
        'status'
    ];
    public function investor(){
        return $this->belongsTo(Investor::class);
    }
    public function property(){
        return $this->belongsTo(Property::class);
    }
    public function config(){
        return $this->belongsTo(PropertyConfiguracion::class, 'config_id');
    }
    public function scopePendientes($query){
        return $query->where('status', 'pendiente');
    }
    public function scopeReservadas($query){
        return $query->where('status', 'reservado');
    }
    public function scopePagadas($query){
        return $query->where('status', 'pagado');
    }
}
