<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class Imagenes extends Model implements AuditableContract
{
    use HasFactory, Auditable, SoftDeletes;

    protected $table = 'property_images';
    
    protected $fillable = [
        'property_id',
        'imagen',
        'path',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model): void {
            if (Auth::check()) {
                $model->created_by = Auth::id();
            }
        });
        
        static::updating(function ($model): void {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
        
        static::deleting(function ($model): void {
            if (Auth::check()) {
                $model->deleted_by = Auth::id();
                // Con SoftDeletes, no necesitas saveQuietly() aquí
            }
        });
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function getUrlAttribute()
    {
        $ruta = public_path("Propiedades/{$this->imagen}");
        if (!file_exists($ruta)) {
            return asset('Propiedades/no-image.png');
        }
        return asset("Propiedades/{$this->imagen}");
    }
}