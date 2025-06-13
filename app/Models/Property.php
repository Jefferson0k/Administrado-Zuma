<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Property extends Model{
    use HasFactory;
    protected $table = 'properties';
    protected $fillable = [
        'nombre', 'distrito', 'descripcion', 'foto',
        'validado', 'fecha_inversion', 'estado',
    ];
    public function inversiones() {
        return $this->hasMany(Investment::class);
    }
    public function subasta() {
        return $this->hasOne(Auction::class);
    }
}
