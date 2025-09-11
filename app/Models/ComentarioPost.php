<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComentarioPost extends Model
{
    protected $table = "comentario_posts";

    protected $fillable = [
        "comentario",
        "post_id",
        "email",

    ];


      public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }



    
}
