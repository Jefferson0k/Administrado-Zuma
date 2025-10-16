<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Newsletter extends Model {
    use HasFactory;
    protected $table = 'newsletters';
    protected $fillable = [
        'email',
        'interested_product',
        'accepted_policy',
    ];
}
