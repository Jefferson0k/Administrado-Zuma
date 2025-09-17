<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class ImagePost extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'image_path'
    ];

    public $timestamps = false;

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
}
