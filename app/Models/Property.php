<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Money\Money;
use Money\Currency as MoneyCurrency;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class Property extends Model implements AuditableContract
{
    use HasFactory, Auditable, SoftDeletes;

    protected $table = 'properties';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'investor_id',
        'nombre',
        'departamento',
        'provincia',
        'distrito',
        'direccion',
        'descripcion',
        'valor_estimado',
        'valor_subasta',
        'valor_requerido',
        'currency_id',
        'estado',
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
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::ulid();
            }
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
                $model->saveQuietly();
            }
        });
    }

    // -------------------------
    // 💰 Mutators y Accessors
    // -------------------------

    // Mutators: Convertir de unidades a centavos al guardar (SOLO para CREATE)
    public function setValorEstimadoAttribute($value)
    {
        // Solo aplicar mutator si es CREATE (no existe en BD)
        if (!$this->exists) {
            $this->attributes['valor_estimado'] = (int) round($value * 100);
        } else {
            // Para UPDATE, asumir que ya viene en centavos
            $this->attributes['valor_estimado'] = (int) $value;
        }
    }

    public function setValorSubastaAttribute($value)
    {
        if (!$this->exists) {
            $this->attributes['valor_subasta'] = $value ? (int) round($value * 100) : null;
        } else {
            $this->attributes['valor_subasta'] = $value ? (int) $value : null;
        }
    }

    public function setValorRequeridoAttribute($value)
    {
        if (!$this->exists) {
            $this->attributes['valor_requerido'] = (int) round($value * 100);
        } else {
            $this->attributes['valor_requerido'] = (int) $value;
        }
    }

    // Accessors: Convertir de centavos a objetos Money
    public function getValorEstimadoAttribute($value)
    {
        try {
            $currencyCode = $this->getCurrencyCode();
            return new Money((int) $value, new MoneyCurrency($currencyCode));
        } catch (Exception $e) {
            Log::warning('Error creating Money object for valor_estimado', [
                'property_id' => $this->id,
                'value' => $value,
                'currency_id' => $this->currency_id,
                'error' => $e->getMessage()
            ]);
            return new Money((int) $value, new MoneyCurrency('PEN'));
        }
    }

    public function getValorSubastaAttribute($value)
    {
        if ($value === null) {
            return null;
        }
        try {
            $currencyCode = $this->getCurrencyCode();
            return new Money((int) $value, new MoneyCurrency($currencyCode));
        } catch (Exception $e) {
            Log::warning('Error creating Money object for valor_subasta', [
                'property_id' => $this->id,
                'value' => $value,
                'currency_id' => $this->currency_id,
                'error' => $e->getMessage()
            ]);
            return new Money((int) $value, new MoneyCurrency('PEN'));
        }
    }

    public function getValorRequeridoAttribute($value)
    {
        try {
            $currencyCode = $this->getCurrencyCode();
            return new Money((int) $value, new MoneyCurrency($currencyCode));
        } catch (Exception $e) {
            Log::warning('Error creating Money object for valor_requerido', [
                'property_id' => $this->id,
                'value' => $value,
                'currency_id' => $this->currency_id,
                'error' => $e->getMessage()
            ]);
            return new Money((int) $value, new MoneyCurrency('PEN'));
        }
    }

    private function getCurrencyCode(): string
    {
        if ($this->relationLoaded('currency') && $this->currency) {
            return $this->currency->codigo;
        }
        if ($this->currency_id) {
            $currency = Currency::find($this->currency_id);
            if ($currency && $currency->codigo) {
                return $currency->codigo;
            }
        }
        return 'PEN';
    }

    // -------------------------
    // Relaciones
    // -------------------------
    public function propertyInvestors()
    {
        return $this->hasMany(PropertyInvestor::class);
    }

    public function subasta()
    {
        return $this->hasOne(Auction::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }

    public function investments()
    {
        return $this->hasMany(Investment::class);
    }

    public function images()
    {
        return $this->hasMany(Imagenes::class, 'property_id');
    }

    public function loanDetail()
    {
        return $this->hasOne(PropertyLoanDetail::class, 'property_id');
    }
    public function getImagenes(): array
    {
        try {
            return $this->images()->get()->map(function ($img) {
                return [
                    'url' => $img->path 
                        ? url("s3/{$img->path}") 
                        : asset('Propiedades/no-image.png'),
                    'descripcion' => $img->description ?? '',
                ];
            })->toArray();
        } catch (Exception $e) {
            Log::error("Error obteniendo imágenes para propiedad {$this->id}", [
                'error' => $e->getMessage()
            ]);
            return [[
                'url' => asset('Propiedades/no-image.png'),
                'descripcion' => 'Sin descripción'
            ]];
        }
    }

    public function paymentSchedules()
    {
        return $this->hasManyThrough(
            PaymentSchedule::class,
            PropertyInvestor::class,
            'property_id',
            'property_investor_id',
            'id',
            'id'
        );
    }

    public function configuraciones()
    {
        return $this->hasMany(PropertyConfiguracion::class);
    }

    public function ultimaConfiguracion()
    {
        return $this->hasOne(PropertyConfiguracion::class)->latestOfMany();
    }

    public function property_configuraciones()
    {
        return $this->hasMany(PropertyConfiguracion::class, 'property_id');
    }

    public function configuracion()
    {
        return $this->hasOne(PropertyConfiguracion::class);
    }

    public function configuracionActiva()
    {
        return $this->hasOne(PropertyConfiguracion::class, 'property_id')->where('estado', 'activa');
    }

    public function investorStatuses()
    {
        return $this->hasMany(InvestorPropertyStatus::class);
    }
}