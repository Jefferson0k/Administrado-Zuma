<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Property extends Model{
    use HasFactory;
    protected $table = 'properties';
    protected $fillable = [
        'nombre', 'distrito', 'descripcion','valor_estimado', 'valor_subasta', 
        'currency_id', 'deadlines_id', 'tea', 'tem', 'estado','departamento','provincia',
        'distrito','direccion',
    ];
    public function inversiones() {
        return $this->hasMany(Investment::class);
    }
    public function subasta() {
        return $this->hasOne(Auction::class);
    }
    public function currency(){
        return $this->belongsTo(Currency::class);
    }
    public function term(){
        return $this->belongsTo(Deadlines::class);
    }
    public function investments(){
        return $this->hasMany(Investment::class);
    }
    public function plazo(){
        return $this->belongsTo(Deadlines::class, 'deadlines_id');
    }
    public function images(){
        return $this->hasMany(Imagenes::class);
    }
}
