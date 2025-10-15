<?php

namespace App\Models;

use App\Enums\MovementStatus;
use App\Enums\MovementType;
use App\Notifications\InvestorAccountActivateNotification;
use App\Notifications\InvestorAccountApprovedNotification;
use App\Notifications\InvestorAccountObservedNotification;
use App\Notifications\InvestorAccountObservedFotoNotification;
use App\Notifications\InvestorAccountRejectedNotification;
use App\Notifications\InvestorDepositApprovalNotification;
use App\Notifications\InvestorDepositPendingNotification;
use App\Notifications\InvestorDepositRejectedNotification;
use App\Notifications\InvestorEmailVerificationNotification;
use App\Notifications\InvestorFullyPaymentNotification;
use App\Notifications\InvestorInvestedNotification;
use App\Notifications\InvestorPartialPaymentNotification;
use App\Notifications\InvestorPasswordResetNotification;
use App\Notifications\InvestorWithdrawApprovedNotification;
use App\Notifications\InvestorWithdrawPendingNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Laravel\Sanctum\HasApiTokens;
use Money\Money;
// App\Models\Investor.php
use Illuminate\Support\Facades\Storage;

use App\Notifications\InvestorAccountObservedDNIEmailNotification;
use App\Notifications\InvestorAccountObservedPepEvidenceNotification;


class Investor extends Authenticatable implements MustVerifyEmail, AuditableContract
{
    use HasApiTokens, HasFactory, Notifiable, HasUlids, Auditable;
    protected $table = 'investors';
    protected $fillable = [
        'name',
        'first_last_name',
        'second_last_name',
        'document',
        'nacionalidad',
        'alias',
        'email',
        'password',
        'telephone',
        'document_front',
        'document_back',
        'profile_photo_path',
        'status',
        'email_verified_at',
        'is_pep',
        'has_relationship_pep',
        'department',
        'province',
        'district',
        'address',
        'api_token',
        'type',
        'asignado',
        'codigo',
        'tipo_documento_id',
        'updated_by',
        'investor_photo_path',
        'file_path',
        'approval1_status',
        'approval1_by',
        'approval1_comment',
        'approval1_at',
        'approval2_status',
        'approval2_by',
        'approval2_comment',
        'approval2_at',

        'whatsapp_verified',
        'whatsapp_verified_at',
        'whatsapp_verification_code',
        'whatsapp_verification_sent_at',
    ];
    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
    ];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_pep' => 'boolean',
        'has_relationship_pep' => 'boolean',

        'whatsapp_verified' => 'boolean',
        'whatsapp_verified_at' => 'datetime',
        'whatsapp_verification_sent_at' => 'datetime',
    ];
    public function investments()
    {
        return $this->hasMany(Investment::class);
    }
    public function aprovacionuseruno()
    {
        return $this->belongsTo(User::class, 'approval1_by');
    }

    public function aprovacionuserdos()
    {
        return $this->belongsTo(User::class, 'approval2_by');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function balances()
    {
        return $this->hasMany(Balance::class);
    }
    public function investment()
    {
        return $this->hasMany(FixedTermInvestment::class);
    }
    public function lastInvestment()
    {
        return $this->hasOne(FixedTermInvestment::class)->latestOfMany();
    }
    public function propertyStatuses()
    {
        return $this->hasMany(InvestorPropertyStatus::class);
    }
    public function createBalance(string $currency, int $amount)
    {
        $balance = new Balance();
        $balance->currency = $currency;
        $balance->amount = $amount;
        $balance->investor_id = $this->id;
        $balance->save();

        return $balance;
    }
    public function sendEmailVerificationNotification()
    {
        $this->notify(new InvestorEmailVerificationNotification());
    }
    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }

    public function codigoAsignado()
    {
        return $this->hasOne(InvestorCode::class);
    }
    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class);
    }
    public function movements()
    {
        return $this->hasMany(Movement::class);
    }
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
    public function withdraws()
    {
        return $this->hasMany(Withdraw::class);
    }
    public function avatar()
    {
        return $this->hasOne(InvestorAvatar::class);
    }
    public function getBalance(string $currency): Balance
    {
        return $this->balances()->where('currency', $currency)->firstOrFail();
    }
    public function getProfilePhotoPathRaw(): ?string
    {
        return $this->attributes['profile_photo_path'];
    }
    public function getProfilePhotoPathAttribute(): ?string
    {
        if (isset($this->attributes['profile_photo_path'])) {
            return env('APP_URL') . '/s3/' . $this->attributes['profile_photo_path'];
        }
        return null;
    }

    public function getInvestorPhotoAttribute(): ?string
    {
        if (isset($this->attributes['investor_photo_path'])) {
            return env('APP_URL') . '/s3/' . $this->attributes['investor_photo_path'];
        }
        return null;
    }
    public function createMovement(
        float $amount,
        MovementType $type,
        Currency $currency,
        MovementStatus $status,
        ?MovementStatus $confirm_status = null,
    ) {
        $movement = new Movement();
        $movement->currency = $currency->value;
        $movement->amount = $amount;
        $movement->type = $type->value;
        $movement->investor_id = $this->id;
        $movement->status = $status;
        $movement->confirm_status = $confirm_status;
        $movement->save();

        return $movement;
    }
    public function addBalance(float $amount, Currency $currency)
    {
        if (!$currency) throw new \Exception('Currency is required', 400);
        $balance = $this->balances()->where('currency', $currency)->first();
        if (!$balance) throw new \Exception("No existe billetera para {$currency}", 404);
        $balance->amount += $amount;
        $balance->save();
        return $balance;
    }
    public function sendBankAccountValidationEmailNotification(BankAccount $bankAccount)
    {
        $this->notify(new InvestorAccountActivateNotification($bankAccount));
    }
    public function sendDepositPendingEmailNotification(Deposit $deposit)
    {
        $this->notify(new InvestorDepositPendingNotification($deposit));
    }
    public function sendDepositApprovalEmailNotification(Deposit $deposit)
    {
        $this->notify(new InvestorDepositApprovalNotification($deposit));
    }
    public function sendDepositRejectedEmailNotification(Deposit $deposit)
    {
        $this->notify(new InvestorDepositRejectedNotification($deposit));
    }
    public function sendWithdrawalPendingEmailNotification(Withdraw $withdraw)
    {
        $this->notify(new InvestorWithdrawPendingNotification($withdraw));
    }
    public function sendWithdrawApprovedEmailNotification(Withdraw $withdraw)
    {
        $this->notify(new InvestorWithdrawApprovedNotification($withdraw));
    }
    public function sendInvestmentEmailNotification(Invoice $invoice, Investment $investment, Company $company)
    {
        $this->notify(new InvestorInvestedNotification($invoice, $investment, $company));
    }
    public function sendInvestmentPartialEmailNotification(Payment $payment, Investment $investment, Money $amountToPay)
    {
        $this->notify(new InvestorPartialPaymentNotification($payment, $investment, $amountToPay));
    }
    public function sendInvestmentFullyPaidEmailNotification(Payment $payment, Investment $investment, Money $netExpectedReturn, Money $itfAmount)
    {
        $this->notify(new InvestorFullyPaymentNotification($payment, $investment, $netExpectedReturn, $itfAmount));
    }
    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class, 'tipo_documento_id', 'id_tipo_documento');
    }
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new InvestorPasswordResetNotification($token));
    }
    public function sendAccountRejectedEmailNotification()
    {
        $this->notify(new InvestorAccountRejectedNotification());
    }

    public function sendAccountApprovedEmailNotification()
    {
        $this->notify(new InvestorAccountApprovedNotification());
    }
    public function sendAccountObservedEmailNotification()
    {
        $this->notify(new InvestorAccountObservedNotification());
    }

    public function sendAccountObservedDNIEmailNotification()
    {
        $this->notify(new InvestorAccountObservedDNIEmailNotification());
    }


    public function sendAccountObservedFotoNotification()
    {
        $this->notify(new InvestorAccountObservedFotoNotification());
    }

    public function sendAccountObservedPepEvidenceNotification()
    {
        $this->notify(new InvestorAccountObservedPepEvidenceNotification());
    }





    public function getDocumentFrontAttribute($value)
    {
        if (!$value) return null;
        return str_starts_with($value, 'http') ? $value : Storage::disk('s3')->url($value);
    }

    public function getDocumentBackAttribute($value)
    {
        if (!$value) return null;
        return str_starts_with($value, 'http') ? $value : Storage::disk('s3')->url($value);
    }

    public function getInvestorPhotoPathAttribute($value)
    {
        if (!$value) return null;
        return str_starts_with($value, 'http') ? $value : Storage::disk('s3')->url($value);
    }

    // If you added evidence columns to Investor (file_path, pep_file_path) and you want the same behavior:
    public function getFilePathAttribute($value)
    {
        if (!$value) return null;
        return str_starts_with($value, 'http') ? $value : Storage::disk('s3')->url($value);
    }

    public function getPepFilePathAttribute($value)
    {
        if (!$value) return null;
        return str_starts_with($value, 'http') ? $value : Storage::disk('s3')->url($value);
    }
    // Agrega estos métodos a tu modelo Investor existente

    /**
     * Scope para inversionistas validados
     */
    public function scopeValidated($query)
    {
        return $query->where('status', 'validated');
    }

    /**
     * Scope para inversionistas con teléfono
     */
    public function scopeWithTelephone($query)
    {
        return $query->whereNotNull('telephone')->where('telephone', '!=', '');
    }

    /**
     * Scope por tipo de inversionista
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Obtener el nombre completo
     */
    public function getFullNameAttribute()
    {
        return trim($this->name . ' ' . $this->first_last_name . ' ' . $this->second_last_name);
    }

    /**
     * Obtener el teléfono formateado para WhatsApp
     */
    public function getFormattedTelephoneAttribute()
    {
        if (!$this->telephone) return null;

        $phone = preg_replace('/[^0-9+]/', '', $this->telephone);

        if (!str_starts_with($phone, '+')) {
            if (str_starts_with($phone, '51')) {
                $phone = '+' . $phone;
            } else {
                $phone = '+51' . $phone;
            }
        }

        return $phone;
    }

    public function scopeWhatsappVerified($query)
    {
        return $query->where('whatsapp_verified', true);
    }

    /**
     * Scope para inversionistas pendientes de verificación de WhatsApp
     */
    public function scopeWhatsappPending($query)
    {
        return $query->where('whatsapp_verified', false)
            ->whereNotNull('telephone')
            ->where('telephone', '!=', '');
    }

    /**
     * Verificar si puede recibir mensajes de WhatsApp
     */
    public function canReceiveWhatsApp()
    {
        return $this->whatsapp_verified && $this->telephone;
    }

    /**
     * Obtener el estado de verificación de WhatsApp
     */
    public function getWhatsappVerificationStatusAttribute()
    {
        if ($this->whatsapp_verified) {
            return 'verified';
        } elseif ($this->whatsapp_verification_sent_at) {
            return 'pending';
        } else {
            return 'not_sent';
        }
    }

    public static function crearOActualizarPorDni(array $data, string $nuevoType)
    {
        $investor = self::where('document', $data['document'])->first();

        if ($investor) {
            // Si el tipo existente es diferente al nuevo, actualizar a mixto
            if ($investor->type !== $nuevoType) {
                $investor->type = 'mixto';
                $investor->update();
            }
        } else {
            // Crear nuevo investor con el tipo indicado
            $data['type'] = $nuevoType;
            $investor = self::create($data);
        }



        return $investor;
    }


    public function hasVerifiedWhatsapp(): bool
    {
        return (bool) $this->whatsapp_verified;
    }

}
