<?php

namespace App\Models;

use App\Notifications\BankAccountRejected;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class BankAccount extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'bank_accounts';

    protected $fillable = [
        'bank_id',
        'type',
        'currency',
        'cc',
        'cci',
        'alias',
        'status',
        'status0',
        'investor_id',
        'comment0',
        'comment',

        'updated0_by',
        'updated0_at',   // NEW
        'updated_by',
        'updates_last_at'


    ];

    protected $casts = [
        'currency' => 'string',
        'status' => 'string', // podrías convertir esto a Enum si lo necesitas
    ];

    /**
     * Relaciones
     */
    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }
    public function sendBankAccountValidationEmail()
    {
        $this->investor->sendBankAccountValidationEmailNotification($this);
    }
    public function sendBankAccountRejectionEmail()
    {
        if ($this->investor && $this->investor->email) {
            $this->investor->notify(new BankAccountRejected($this));
        }
    }

    public function attachments()
    {
        return $this->hasMany(\App\Models\BankAccountAttachment::class);
    }


     public function updated0By(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated0_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

}
