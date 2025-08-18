<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'updated_user_id',
        'titulo',
        'contenido',
        'enlaces',
        'imagen',
        'state_id',
        'fecha_programada',
        'fecha_publicacion',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function updated_user()
    {
        return $this->belongsTo(User::class, 'updated_user_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'post_categories', 'post_id', 'category_id');
    }

    public function ratings()
    {
        return $this->belongsToMany(Rating::class, 'post_ratings', 'post_id', 'rating_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }
    public function images()
{
    return $this->hasMany(PostImage::class, 'post_id');
}
}
