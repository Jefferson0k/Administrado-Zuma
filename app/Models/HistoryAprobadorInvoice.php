<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoryAprobadorInvoice extends Model
{
    //
    protected $table = 'history_aprobadores_invoices';
    protected $fillable = [
        'invoice_id',
        'approval1_status',
        'approval1_by',
        'approval1_comment',
        'approval1_at',
        'approval2_status',
        'approval2_by',
        'approval2_comment',
        'approval2_at',
        'status_conclusion',
        'approval_conclusion_by',
        'approval_conclusion_comment',
        'approval_conclusion_at',
    ];
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
    public function approval1By()
    {
        return $this->belongsTo(User::class, 'approval1_by');
    }
    public function approval2By()
    {
        return $this->belongsTo(User::class, 'approval2_by');
    }
    public function approvalConclusionBy()
    {
        return $this->belongsTo(User::class, 'approval_conclusion_by');
    }
}
