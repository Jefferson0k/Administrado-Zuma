<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class Subsector extends Model implements AuditableContract{
    use SoftDeletes, Auditable;
    protected $fillable = [
        'name',
        'sector_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }
    public function companies()
    {
        return $this->hasMany(Company::class, 'subsector_id', 'id');
    }

}
