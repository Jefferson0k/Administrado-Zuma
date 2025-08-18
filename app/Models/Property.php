<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Money\Money;
use Money\Currency as MoneyCurrency;

class Property extends Model{
    use HasFactory;
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
    ];

    protected static function boot(){
        parent::boot();
        static::creating(function ($model): void {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::ulid();
            }
        });
    }

    // -------------------------
    // 💰 Accessors & Mutators
    // -------------------------
    public function setValorEstimadoAttribute($value){
        $this->attributes['valor_estimado'] = (int) round($value * 100);
    }

    public function setValorSubastaAttribute($value){
        $this->attributes['valor_subasta'] = $value ? (int) round($value * 100) : null;
    }

    public function setValorRequeridoAttribute($value){
        $this->attributes['valor_requerido'] = (int) round($value * 100);
    }

    public function getValorEstimadoAttribute($value){
        try {
            $currencyCode = $this->getCurrencyCode();
            return new Money((int) $value, new MoneyCurrency($currencyCode));
        } catch (\Exception $e) {
            Log::warning('Error creating Money object for valor_estimado', [
                'property_id' => $this->id,
                'value' => $value,
                'currency_id' => $this->currency_id,
                'error' => $e->getMessage()
            ]);
            return new Money((int) $value, new MoneyCurrency('PEN'));
        }
    }

    public function getValorSubastaAttribute($value){
        if ($value === null) {
            return null;
        }
        try {
            $currencyCode = $this->getCurrencyCode();
            return new Money((int) $value, new MoneyCurrency($currencyCode));
        } catch (\Exception $e) {
            Log::warning('Error creating Money object for valor_subasta', [
                'property_id' => $this->id,
                'value' => $value,
                'currency_id' => $this->currency_id,
                'error' => $e->getMessage()
            ]);
            return new Money((int) $value, new MoneyCurrency('PEN'));
        }
    }

    public function getValorRequeridoAttribute($value){
        try {
            $currencyCode = $this->getCurrencyCode();
            return new Money((int) $value, new MoneyCurrency($currencyCode));
        } catch (\Exception $e) {
            Log::warning('Error creating Money object for valor_requerido', [
                'property_id' => $this->id,
                'value' => $value,
                'currency_id' => $this->currency_id,
                'error' => $e->getMessage()
            ]);
            return new Money((int) $value, new MoneyCurrency('PEN'));
        }
    }

    /**
     * Get currency code safely
     */
    private function getCurrencyCode(): string
    {
        // Si ya tenemos la relación cargada
        if ($this->relationLoaded('currency') && $this->currency) {
            return $this->currency->codigo;
        }

        // Si no está cargada pero tenemos currency_id, buscarla
        if ($this->currency_id) {
            $currency = \App\Models\Currency::find($this->currency_id);
            if ($currency && $currency->codigo) {
                return $currency->codigo;
            }
        }

        // Fallback a PEN
        return 'PEN';
    }

    // -------------------------
    // Relaciones
    // -------------------------
    public function subasta(){
        return $this->hasOne(Auction::class);
    }

    public function currency(){
        return $this->belongsTo(\App\Models\Currency::class);
    }

    public function investor(){
        return $this->belongsTo(Investor::class);
    }

    public function investments(){
        return $this->hasMany(Investment::class);
    }

    public function images(){
        return $this->hasMany(Imagenes::class);
    }

    public function loanDetail(){
        return $this->hasOne(PropertyLoanDetail::class, 'property_id');
    }

    public function getImagenes(): array{
        $rutaCarpeta = public_path("Propiedades/{$this->id}");
        $imagenes = [];
        if (File::exists($rutaCarpeta)) {
            $archivos = File::files($rutaCarpeta);
            foreach ($archivos as $archivo) {
                $imagenes[] = asset("Propiedades/{$this->id}/" . $archivo->getFilename());
            }
        }
        if (empty($imagenes)) {
            $imagenes[] = asset('Propiedades/no-image.png');
        }
        return $imagenes;
    }

    public function paymentSchedules(){
        return $this->hasManyThrough(
            PaymentSchedule::class,
            PropertyInvestor::class,
            'property_id',
            'property_investor_id',
            'id',
            'id'
        );
    }

    public function configuraciones() {
        return $this->hasMany(PropertyConfiguracion::class);
    }

    public function ultimaConfiguracion() {
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

    public function configuracionActiva(){
        return $this->hasOne(PropertyConfiguracion::class, 'property_id')->where('estado', 'activa');
    }

    public function investorStatuses(){
        return $this->hasMany(InvestorPropertyStatus::class);
    }
}