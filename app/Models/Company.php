<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'risk',
        'business_name',
        'sector_id',
        'subsector_id',
        'incorporation_year',
        'sales_volume',
        'document',
        'link_web_page',
        'description',
        'created_by',
        'updated_by'
    ];

    public $timestamps = true;

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function subsector(): BelongsTo
    {
        return $this->belongsTo(Subsector::class);
    }
}
