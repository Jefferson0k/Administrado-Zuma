<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PropertyConfiguracion extends Model {
    use HasFactory;
    protected $table = 'property_configuracions';
    protected $fillable = [
        'property_id',
        'deadlines_id',
        'tea',
        'tem',
        'tipo_cronograma',
        'riesgo',
        'estado'
    ];
    public function plazo() {
        return $this->belongsTo(Deadlines::class, 'deadlines_id');
    }
    public function property() {
        return $this->belongsTo(Property::class, 'property_id');
    }
}
