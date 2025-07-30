<?php

namespace App\Models;

use App\Notifications\InvestorEmailVerificationNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Laravel\Sanctum\HasApiTokens;

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
}
