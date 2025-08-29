<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestorAvatar extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'investor_id',
        'avatar_type',
        'clothing_color',
        'background_color',
        'medal',
        'medal_position',
        'hat',
        'hat_position',
        'trophy',
        'other',
    ];

    protected $casts = [
        'medal_position' => 'array',
        'hat_position' => 'array',
    ];

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }
}
