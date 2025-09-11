<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\LogOptions;
use Laravel\Sanctum\HasApiTokens; 
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class User extends Authenticatable{

    use HasFactory, Notifiable, HasRoles, LogsActivity, HasApiTokens, SoftDeletes, Auditable;
    
    protected $fillable = [
        'name',
        'dni',
        'apellidos',
        'email',
        'password',
        'status',
        'restablecimiento',
        'cargo_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected function casts(): array{
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
        ];
    }
    public function getActivitylogOptions(): LogOptions{
        return LogOptions::defaults()
            ->logOnly(['name', 'dni', 'apellidos', 'email', 'status', 'cargo_id'])
            ->useLogName('usuario')
            ->logOnlyDirty();
    }
    public function isOnline(): bool{
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
    public function inversionista(){
        return $this->hasOne(Investor::class, 'user_id');
    }
    public function cargo(){
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }
}
