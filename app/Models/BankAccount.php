<?php

namespace App\Models;

use App\Notifications\BankAccountRejected;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

use App\Notifications\BankAccountObserved;
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
        'status_conclusion',
        'investor_id',
        'comment0',
        'comment',

        'updated0_by',
        'updated0_at',   // NEW
        'updated_by',
        'updated_last_at'


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

    public function sendBankAccountObservedEmail(?string $message = null)
    {
        if ($this->investor && $this->investor->email) {
            $this->investor->notify(new BankAccountObserved($this, $message));
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
