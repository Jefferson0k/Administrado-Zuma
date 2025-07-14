<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentFrequency extends Model{
    use HasFactory;
    protected $table = 'payment_frequencies';
    protected $fillable = ['nombre', 'dias'];
}
