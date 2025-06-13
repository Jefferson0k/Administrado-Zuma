<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Auction extends Model{
    
    use HasFactory;
    protected $table = 'auctions';
    protected $fillable = [
        'property_id', 'monto_inicial', 'dia_subasta',
        'hora_inicio', 'hora_fin', 'tiempo_finalizacion',
        'estado', 'ganador_id'
    ];
    public function propiedad() {
        return $this->belongsTo(Property::class);
    }
    public function pujas() {
        return $this->hasMany(Bid::class);
    }
    public function ganador() {
        return $this->belongsTo(User::class, 'ganador_id');
    }
}
