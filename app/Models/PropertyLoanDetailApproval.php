<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class PropertyLoanDetailApproval extends Model
{
    use HasFactory;

    protected $table = 'property_loan_detail_approvals';

    protected $fillable = [
        'loan_detail_id',
        'status',
        'approved_by',
        'comment',
        'approved_at',
    ];

    protected $dates = ['approved_at'];

    // -------------------------
    // Relaciones
    // -------------------------

    public function loanDetail()
    {
        return $this->belongsTo(PropertyLoanDetail::class, 'loan_detail_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // -------------------------
    // Accessors / Mutators
    // -------------------------

    public function getApprovedAtAttribute($value)
    {
        return $value
            ? Carbon::parse($value)->format('d/m/Y H:i')
            : null;
    }

    public function getApprovedByNameAttribute()
    {
        return $this->user
            ? $this->user->name . ' ' . ($this->user->apellidos ?? '')
            : null;
    }
}
