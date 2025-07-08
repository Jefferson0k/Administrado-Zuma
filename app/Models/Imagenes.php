<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Imagenes extends Model{
    use HasFactory;
    protected $table = 'property_images';
    protected $fillable = [
        'property_id',
        'imagen',
    ];
    public function property(){
        return $this->belongsTo(Property::class);
    }
    public function getUrlAttribute(){
        $ruta = public_path("Propiedades/{$this->imagen}");
        if (!file_exists($ruta)) {
            return asset('Propiedades/no-image.png');
        }
        return asset("Propiedades/{$this->imagen}");
    }
}