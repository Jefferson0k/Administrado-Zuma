<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Auditable;

class Company extends Model{
    use HasFactory, HasUlids, Auditable;
    protected $fillable = [
        'name',
        'risk',
        'business_name',
        'sector_id',
        'subsector_id',
        'incorporation_year',
        'sales_volume',
        'document',
        'link_web_page',
        'description',
        'moneda',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    public $timestamps = true;
    protected $casts = [
        'sales_volume' => 'decimal:2',
    ];
    public function invoices(): HasMany {
        return $this->hasMany(Invoice::class);
    }
    public function sector(): BelongsTo {
        return $this->belongsTo(Sector::class);
    }
    public function subsector(): BelongsTo {
        return $this->belongsTo(Subsector::class);
    }
    public function finances() {
        return $this->hasOne(CompanyFinance::class, 'company_id', 'id');
    }
    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updater(): BelongsTo {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function deleter(): BelongsTo {
        return $this->belongsTo(User::class, 'deleted_by');
    }
    public function getCurrencySymbolAttribute() {
        return match($this->moneda) {
            'USD' => '$',
            'PEN' => 'S/',
            'BOTH' => 'S/ / $',
            default => 'S/'
        };
    }
    public function usesPenCurrency() {
        return in_array($this->moneda, ['PEN', 'BOTH']);
    }
    public function usesUsdCurrency() {
        return in_array($this->moneda, ['USD', 'BOTH']);
    }
    public function usesBothCurrencies() {
        return $this->moneda === 'BOTH';
    }
    public function getPrimarySalesVolumeAttribute() {
        if (!$this->finances) {
            return $this->sales_volume;
        }
        return match($this->moneda) {
            'PEN' => $this->finances->sales_volume_pen,
            'USD' => $this->finances->sales_volume_usd,
            'BOTH' => $this->finances->getTotalSalesVolumeAttribute(),
            default => $this->finances->sales_volume_pen ?? $this->sales_volume
        };
    }
    public function getFullAddressAttribute() {
        $addressParts = array_filter([
            $this->direccion ?? null,
            $this->distrito ?? null,
            $this->provincia ?? null,
            $this->departamento ?? null
        ]);

        return implode(', ', $addressParts);
    }
}
