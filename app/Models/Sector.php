<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sector extends Model
{
    protected $fillable = [
        'name',
    ];
    public function subsectors()
    {
        return $this->hasMany(Subsector::class);
    }

    public function subsector(): HasMany
    {
        return $this->hasMany(Subsector::class);
    }
}
