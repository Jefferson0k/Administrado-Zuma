<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Laravel\Sanctum\HasApiTokens;

class Investor extends Authenticatable implements MustVerifyEmail{
    use HasApiTokens, HasFactory, Notifiable, HasUlids;
    protected $table = 'investors';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'name', 'first_last_name', 'second_last_name', 'alias',
        'document', 'email', 'password', 'telephone',
        'document_front', 'document_back', 'monto', 'status', 'asignado', 'type'
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $casts = [
        'email_verified_at' => 'datetime',
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
    public function createBalance(string $currency, int $amount)
    {
        $balance = new Balance();
        $balance->currency = $currency;
        $balance->amount = $amount;
        $balance->investor_id = $this->id;
        $balance->save();

        return $balance;
    }
}
