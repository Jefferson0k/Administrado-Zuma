<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DepositAttachment extends Model
{
    use HasUlids;

    protected $table = 'deposit_attachments';
    protected $fillable = [
        'deposit_id',
        'path',
        'name',
        'mime',
        'size',
        'uploaded_by',
    ];

    protected $appends = ['url', 'is_image', 'ext'];

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class);
    }

    public function getUrlAttribute(): ?string
    {
        return $this->path ? url('/s3/' . ltrim($this->path, '/')) : null;
    }


    public function getIsImageAttribute(): bool
    {
        return is_string($this->mime) && str_starts_with(strtolower($this->mime), 'image/');
    }

    public function getExtAttribute(): ?string
    {
        if (!$this->name) return null;
        $pos = strrpos($this->name, '.');
        return $pos !== false ? strtolower(substr($this->name, $pos + 1)) : null;
    }
}
