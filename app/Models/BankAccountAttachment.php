<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class BankAccountAttachment extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'bank_account_attachments';

    protected $fillable = [
        'bank_account_id',
        'original_name',
        'path',
        'mime_type',
        'size',
        'uploaded_by',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    // (opcional)
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Helper para URL pública (disco "public")
    public function getUrlAttribute(): string
    {
        return \Storage::disk('public')->url($this->path);
    }
}
