<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductAccount extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'entidad', 'numero_cuenta', 'moneda'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function mortgageInvestments()
    {
        return $this->hasMany(MortgageInvestment::class);
    }
}
