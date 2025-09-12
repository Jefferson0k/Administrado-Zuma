<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class PostImage extends Model
{
     use HasFactory;
    protected $table = 'posts_image';

    protected $fillable = [
        'post_id',
        'image_path',
    ];

     public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    
}
