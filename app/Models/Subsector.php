<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subsector extends Model
{
    protected $fillable = ['name', 'sector_id'];

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }
}