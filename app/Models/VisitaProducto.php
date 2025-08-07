<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitaProducto extends Model{
    use HasFactory;
    protected $table = 'visitas_productos';
    protected $fillable = [
        'ip',
        'producto_id',
        'user_agent',
        'fingerprint'
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public function producto(){
        return $this->belongsTo(Product::class, 'producto_id');
    }
}