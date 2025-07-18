<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\LogOptions;
use Laravel\Sanctum\HasApiTokens; 
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,HasRoles,LogsActivity,HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
		'email',
		'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function getActivitylogOptions(): LogOptions{
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'username', 'status'])
            ->useLogName('usuario')
            ->logOnlyDirty();
    }
    public function isOnline(): bool    {
        return cache()->has('user-is-online-' . $this->id);
    }
    public function invoices(){
        return $this->hasMany(Invoice::class);
    }
    public function investments(){
        return $this->hasMany(Investment::class);
    }
    public function bids(){
        return $this->hasMany(Bid::class);
    }
    public function investor(){
        return $this->hasOne(Investor::class, 'user_id');
    }

    public function inversionista()
    {
        return $this->hasOne(Investor::class, 'user_id');
    }


}
