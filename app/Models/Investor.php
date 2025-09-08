<?php

namespace App\Models;

use App\Enums\MovementStatus;
use App\Enums\MovementType;
use App\Notifications\InvestorAccountActivateNotification;
use App\Notifications\InvestorAccountApprovedNotification;
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
use Laravel\Sanctum\HasApiTokens;
use Money\Money;

class Investor extends Authenticatable implements MustVerifyEmail{
    use HasApiTokens, HasFactory, Notifiable, HasUlids;
    protected $table = 'investors';
    protected $fillable = [
        'name',
        'first_last_name',
        'second_last_name',
        'document',
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
        'updated_by',
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
    ];
    public function investments(){
        return $this->hasMany(Investment::class);
    }
    public function balances(){
        return $this->hasMany(Balance::class);
    }
    public function investment(){
        return $this->hasMany(FixedTermInvestment::class);
    }
    public function lastInvestment(){
        return $this->hasOne(FixedTermInvestment::class)->latestOfMany();
    }
    public function propertyStatuses(){
        return $this->hasMany(InvestorPropertyStatus::class);
    }
    public function createBalance(string $currency, int $amount){
        $balance = new Balance();
        $balance->currency = $currency;
        $balance->amount = $amount;
        $balance->investor_id = $this->id;
        $balance->save();

        return $balance;
    }
    public function sendEmailVerificationNotification(){
        $this->notify(new InvestorEmailVerificationNotification());
    }
    public function codigoAsignado(){
        return $this->hasOne(InvestorCode::class);
    }
    public function bankAccounts(){
        return $this->hasMany(BankAccount::class);
    }
    public function movements(){
        return $this->hasMany(Movement::class);
    }
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
    public function withdraws(){
        return $this->hasMany(Withdraw::class);
    }
    public function avatar(){
        return $this->hasOne(InvestorAvatar::class);
    }
    public function getBalance(string $currency): Balance{
        return $this->balances()->where('currency', $currency)->firstOrFail();
    }
    public function getProfilePhotoPathRaw(): ?string{
        return $this->attributes['profile_photo_path'];
    }
    public function getProfilePhotoPathAttribute(): ?string{
        if (isset($this->attributes['profile_photo_path'])) {
            return env('APP_URL') . '/s3/' . $this->attributes['profile_photo_path'];
        }
        return null;
    }
    public function getDocumentFrontAttribute(): ?string{
        if (isset($this->attributes['document_front'])) {
            return env('APP_URL') . '/s3/' . $this->attributes['document_front'];
        }
        return null;
    }
    public function getDocumentBackAttribute(): ?string{
        if (isset($this->attributes['document_back'])) {
            return env('APP_URL') . '/s3/' . $this->attributes['document_back'];
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
    public function addBalance(float $amount, Currency $currency){
        if (!$currency) throw new \Exception('Currency is required', 400);
        $balance = $this->balances()->where('currency', $currency)->first();
        if (!$balance) throw new \Exception("No existe billetera para {$currency}", 404);
        $balance->amount += $amount;
        $balance->save();
        return $balance;
    }
    public function sendBankAccountValidationEmailNotification(BankAccount $bankAccount){
        $this->notify(new InvestorAccountActivateNotification($bankAccount));
    }
    public function sendDepositPendingEmailNotification(Deposit $deposit){
        $this->notify(new InvestorDepositPendingNotification($deposit));
    }
    public function sendDepositApprovalEmailNotification(Deposit $deposit){
        $this->notify(new InvestorDepositApprovalNotification($deposit));
    }
    public function sendDepositRejectedEmailNotification(Deposit $deposit){
        $this->notify(new InvestorDepositRejectedNotification($deposit));
    }
    public function sendWithdrawalPendingEmailNotification(Withdraw $withdraw){
        $this->notify(new InvestorWithdrawPendingNotification($withdraw));
    }
    public function sendWithdrawApprovedEmailNotification(Withdraw $withdraw){
        $this->notify(new InvestorWithdrawApprovedNotification($withdraw));
    }
    public function sendInvestmentEmailNotification(Invoice $invoice, Investment $investment, Company $company){
        $this->notify(new InvestorInvestedNotification($invoice, $investment, $company));
    }
    public function sendInvestmentPartialEmailNotification(Payment $payment, Investment $investment, Money $amountToPay){
        $this->notify(new InvestorPartialPaymentNotification($payment, $investment, $amountToPay));
    }
    public function sendInvestmentFullyPaidEmailNotification(Payment $payment, Investment $investment, Money $netExpectedReturn, Money $itfAmount)
    {
        $this->notify(new InvestorFullyPaymentNotification($payment, $investment, $netExpectedReturn, $itfAmount));
    }
    public function sendPasswordResetNotification($token){
        $this->notify(new InvestorPasswordResetNotification($token));
    }
    public function sendAccountRejectedEmailNotification(){
        $this->notify(new InvestorAccountRejectedNotification());
    }

    public function sendAccountApprovedEmailNotification()
    {
        $this->notify(new InvestorAccountApprovedNotification());
    }
}
