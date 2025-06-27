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
}