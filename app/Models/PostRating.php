<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostRating extends Model
{
    use HasFactory;
    protected $table = "posts_ratings";

    protected $fillable = [
        'post_id',
        'rating_id'
    ];

    public $timestamps = false;

}
