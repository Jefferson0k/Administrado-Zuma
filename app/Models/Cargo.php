<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Auditable;

class Cargo extends Model{
    use HasFactory, SoftDeletes, Auditable;
    protected $fillable = [
        'nombre',
        'descripcion',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    public function users(){
        return $this->hasMany(User::class);
    }
}
