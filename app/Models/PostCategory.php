<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class PostCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'category_id'
    ];

    public $timestamps = false;
}
