<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
     use HasFactory;
        protected $table = 'rating';


    protected $fillable = [
        'post_id',
        'ip',
        'estrellas',
    ];

    public $timestamps = false;


    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
}
