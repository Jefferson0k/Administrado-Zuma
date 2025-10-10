<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PropertyConfiguracionApproval extends Model{
    use HasFactory;
    protected $table = 'property_configuracion_approvals';
    protected $fillable = [
        'configuracion_id',
        'approved_by',
        'status',
        'comment',
        'approved_at',
    ];
    protected $casts = [
        'approved_at' => 'datetime',
    ];
    public function configuracion(){
        return $this->belongsTo(PropertyConfiguracion::class, 'configuracion_id');
    }
    public function approvedBy(){
        return $this->belongsTo(User::class, 'approved_by');
    }
    public static function registerApproval($configuracionId, string $status, ?string $comment = null){
        $userId = Auth::id();
        return self::updateOrCreate(
            [
                'configuracion_id' => $configuracionId,
                'approved_by' => $userId,
            ],
            [
                'status' => $status,
                'comment' => $comment,
                'approved_at' => now(),
            ]
        );
    }
    public static function areAllApproved($solicitudId): bool{
        $configIds = PropertyConfiguracion::where('solicitud_id', $solicitudId)->pluck('id');
        $approvedCount = self::whereIn('configuracion_id', $configIds)
            ->where('status', 'approved')
            ->distinct('configuracion_id')
            ->count('configuracion_id');
        return $configIds->count() >= 2 && $approvedCount >= 2;
    }
}
