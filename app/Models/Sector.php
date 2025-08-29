<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class Sector extends Model implements AuditableContract
{
    use SoftDeletes, Auditable;

    protected $fillable = [
        'name',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Relación con subsectors
     */
    public function subsectors(): HasMany
    {
        return $this->hasMany(Subsector::class);
    }
}
