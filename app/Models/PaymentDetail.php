<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'invoice_id',
        'investment_id',
        'investor_id',
        'company_id',
        'loan_number',
        'ruc_proveedor',
        'invoice_number',
        'ruc_aceptante',
        'currency',
        'invoice_amount',
        'investment_amount',
        'investment_return',
        'amount_paid',
        'return_efectivizado',
        'payment_date',
        'due_date',
        'payment_type',
        'status',
        'situation',
        'payment_number',
        'observations',
    ];

    // 🔗 Relaciones
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function investment()
    {
        return $this->belongsTo(Investment::class);
    }

    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // 🔢 Casts automáticos
    protected $casts = [
        'payment_date' => 'date',
        'due_date' => 'date',
        'invoice_amount' => 'integer',
        'investment_amount' => 'integer',
        'investment_return' => 'integer',
        'amount_paid' => 'integer',
        'return_efectivizado' => 'integer',
    ];
}
