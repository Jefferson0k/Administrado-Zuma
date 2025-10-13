<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccountDestino extends Model
{
    //

    protected $table = 'bank_account_destinos';
    
    protected $fillable = [
        'bank_id',
        'type',
        'currency',
        'cc',
        'cci',
        'alias',
    ];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    protected $casts = [
    'id' => 'string',
];
}
