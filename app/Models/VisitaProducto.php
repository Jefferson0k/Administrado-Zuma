<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitaProducto extends Model{
    protected $table = 'visitas_productos';
    protected $fillable = [
        'ip',
        'producto_id',
    ];
    public function producto(): BelongsTo{
        return $this->belongsTo(Product::class, 'producto_id');
    }
}
