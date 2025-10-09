<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'solicitud_id',
        'status',
        'approved_by',
        'comment',
        'approved_at',
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
