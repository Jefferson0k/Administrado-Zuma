<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Invoice extends Model{
    use HasFactory, SoftDeletes;
    protected $table = 'invoices';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id',
        'invoice_code',
        'amount',
        'financed_amount_by_garantia',
        'financed_amount',
        'paid_amount',
        'rate',
        'due_date',
        'status',
        'company_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    protected static function boot(){
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::ulid()->toBase32();
            }

            if (empty($model->invoice_code)) {
                $model->invoice_code = Str::uuid()->toString();
            }
        });
    }
    public function creator(){
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updater(){
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function deleter(){
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
