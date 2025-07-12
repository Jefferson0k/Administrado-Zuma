<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
class Property extends Model{
    use HasFactory;
    protected $table = 'properties';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
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
        'deadlines_id',
        'tea',
        'tem',
        'estado',
        'tipo_cronograma',
        'riesgo',
    ];
    protected static function boot(){
        parent::boot();
        static::creating(function ($model) {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::ulid();
            }
        });
    }
    public function subasta(){
        return $this->hasOne(Auction::class);
    }
    public function currency(){
        return $this->belongsTo(Currency::class);
    }
    public function plazo(){
        return $this->belongsTo(Deadlines::class, 'deadlines_id');
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
            PaymentSchedule::class,      // Modelo destino
            PropertyInvestor::class,     // Modelo intermedio
            'property_id',               // Foreign key en PropertyInvestor
            'property_investor_id',      // Foreign key en PaymentSchedule
            'id',                        // Clave local en Property
            'id'                         // Clave local en PropertyInvestor
        );
    }
}
