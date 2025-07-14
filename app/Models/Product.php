<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'descripcion'];

    public function accounts()
    {
        return $this->hasMany(ProductAccount::class);
    }

    public function mortgageInvestments()
    {
        return $this->hasMany(MortgageInvestment::class);
    }
}
