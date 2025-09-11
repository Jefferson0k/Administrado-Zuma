<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PostProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'product_id'
    ];

    public $timestamps = false;

    /*public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function product()
    {
        return $this->hasOne(Product::class);
    }*/
}
