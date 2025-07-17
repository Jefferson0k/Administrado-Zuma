<?php

namespace App\Models;

use App\Enums\MovementStatus;
use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
class Movement extends Model{
    use HasFactory;
    protected $table = 'movements';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'amount',
        'type',
        'currency',
        'status',
        'confirm_status',
        'description',
        'investor_id',
        'currency',
    ];
    protected $casts = [
        'type' => MovementType::class,
        'status' => MovementStatus::class,
        'confirm_status' => MovementStatus::class,
        'amount' => 'decimal:2',
    ];
    public function investor(){
        return $this->belongsTo(Investor::class);
    }
    public function deposit(){
        return $this->hasOne(Deposit::class, 'movement_id');
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::ulid()->toBase32();
            }
        });
    }
}
