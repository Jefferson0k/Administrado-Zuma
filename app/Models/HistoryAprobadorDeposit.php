<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoryAprobadorDeposit extends Model
{
    protected $table = 'history_aprobadores_deposits';
    // --- IGNORE ---
    protected $fillable = [
        'deposit_id',
        'approval1_status',
        'approval1_by',
        'approval1_comment',
        'approval1_at',
        'approval2_status',
        'approval2_by',
        'approval2_comment',
        'approval2_at',
    ];
    public function deposit()
    {
        return $this->belongsTo(Deposit::class, 'deposit_id');
    }
    public function approval1By()
    {
        return $this->belongsTo(User::class, 'approval1_by');
    }
    public function approval2By()
    {
        return $this->belongsTo(User::class, 'approval2_by');
    
    }

}
