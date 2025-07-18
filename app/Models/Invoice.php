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
        'invoice_code',
        'amount',
        'currency',
        'financed_amount_by_garantia',
        'financed_amount',
        'paid_amount',
        'rate',
        'due_date',
        'estimated_pay_date',
        'status',
        'company_id',
        'loan_number',
        'RUC_client',
        'invoice_number'
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
