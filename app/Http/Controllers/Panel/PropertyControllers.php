<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Property\PropertyUpdateRequest;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Resources\Subastas\Property\PropertyOnliene;
use App\Http\Resources\Subastas\Property\PropertyResource;
use App\Http\Resources\Subastas\Property\PropertyShowResource;
use App\Http\Resources\Subastas\Property\PropertyUpdateResource;
use App\Models\Auction;
use App\Models\Deadlines;
use App\Models\Imagenes;
use App\Models\PaymentSchedule;
use App\Models\Property;
use App\Models\PropertyInvestor;
use App\Pipelines\FilterByCurrency;
use App\Pipelines\FilterByEstado;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use App\Pipelines\FilterBySearch;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PropertyControllers extends Controller{
    public function store(StorePropertyRequest $request){
        $data = $request->validated();
        $property = Property::create($data);
        if ($request->hasFile('imagenes')) {
            $this->handleImagenesUpload($request, $property->id);
        }
        return response()->json([
            'message' => 'Propiedad registrada exitosamente.',
            'property' => $property->load('images'),
        ], 201);
    }
    public function showProperty(string $id){
        $property = Property::with(['currency', 'plazo', 'images'])->findOrFail($id);
        return response()->json($property);
    }
    public function updateProperty(StorePropertyRequest $request, string $id){
        $property = Property::findOrFail($id);
        $property->update($request->validated());        
        if ($request->has('imagenes_eliminar')) {
            $imagenesAEliminar = $request->input('imagenes_eliminar');
            
            foreach ($imagenesAEliminar as $imagenId) {
                $image = $property->images()->find($imagenId);
                if ($image) {
                    $imagePath = public_path("Propiedades/{$property->id}/{$image->imagen}");
                    if (File::exists($imagePath)) {
                        File::delete($imagePath);
                    }
                    $image->delete();
                }
            }
        }
        if ($request->hasFile('imagenes')) {
            $this->handleImagenesUpload($request, $property->id);
        }
        return response()->json(['message' => 'Propiedad actualizada correctamente.'], 200);
    }
    public function delete(string $id){
        $property = Property::findOrFail($id);
        foreach ($property->images as $image) {
            $imagePath = public_path("Propiedades/{$property->id}/{$image->imagen}");
            if (File::exists($imagePath)) {
                File::delete($imagePath);
            }
            $image->delete();
        }
        $directory = public_path("Propiedades/{$property->id}");
        if (File::isDirectory($directory)) {
            File::deleteDirectory($directory);
        }
        $property->delete();
        return response()->json(['message' => 'Propiedad eliminada correctamente.'], 200);
    }
    public function index(Request $request){
        try {
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search', '');
            $estado = $request->input('estado', '');
            $currencyId = $request->input('currency_id');

            $query = app(Pipeline::class)
                ->send(Property::query())
                ->through([
                    new FilterBySearch($search),
                    new FilterByEstado($estado),
                    new FilterByCurrency($currencyId),
                ])
                ->thenReturn();

            return PropertyResource::collection($query->paginate($perPage));
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al cargar los datos',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
    public function indexSubastaTotoal(Request $request){
        try {
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search', '');
            $currencyId = $request->input('currency_id');

            $query = app(Pipeline::class)
                ->send(Property::where('estado', 'activa'))
                ->through([
                    new FilterBySearch($search),
                    new FilterByCurrency($currencyId),
                ])
                ->thenReturn();

            return PropertyResource::collection($query->paginate($perPage));
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al cargar los datos',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
    public function show($id){
        $property = Property::with('subasta')->find($id);
        if (!$property) {
            return response()->json(['error' => 'Propiedad no encontrada'], 404);
        }
        return new PropertyUpdateResource($property);
    }
    public function showCustumer($id){
        $property = Property::with('subasta')->find($id);
        if (!$property) {
            return response()->json(['error' => 'Propiedad no encontrada'], 404);
        }
        return new PropertyShowResource($property);
    }
    public function update(PropertyUpdateRequest $request, $id){
        try {
            $property = Property::findOrFail($id);
            $nuevoEstado = 'en_subasta';

            $property->valor_subasta = $request->monto_inicial;

            $diaSubasta = $request->dia_subasta;
            $horaInicio = $request->hora_inicio;
            $horaFin = $request->hora_fin;

            $fechaInicio = Carbon::createFromFormat('Y-m-d H:i:s', "$diaSubasta $horaInicio");
            $fechaFin = Carbon::createFromFormat('Y-m-d H:i:s', "$diaSubasta $horaFin");

            if ($fechaFin->lessThanOrEqualTo($fechaInicio)) {
                return response()->json([
                    'message' => 'La hora de fin debe ser mayor a la de inicio'
                ], 422);
            }

            if ($property->valor_subasta <= 0) {
                return response()->json([
                    'message' => 'El valor de subasta debe ser mayor a cero antes de iniciar una subasta.'
                ], 422);
            }

            if (!$property->subasta) {
                $property->subasta()->create([
                    'monto_inicial' => $property->valor_subasta,
                    'dia_subasta' => $diaSubasta,
                    'hora_inicio' => $horaInicio,
                    'hora_fin' => $horaFin,
                    'tiempo_finalizacion' => $fechaFin,
                    'estado' => 'activa',
                ]);
            }

            $property->estado = $nuevoEstado;
            $property->deadlines_id = $request->deadlines_id;
            $property->save();

            $propertyInvestor = PropertyInvestor::create([
                'property_id' => $property->id,
                'investor_id' => null,
                'amount' => $property->valor_estimado,
                'status' => 'pendiente',
            ]);

            $this->generatePaymentSchedule($propertyInvestor->id, $property);

            return response()->json([
                'message' => 'Estado actualizado correctamente y cronograma generado.',
                'property' => $property,
                'property_investor_id' => $propertyInvestor->id,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    private function generatePaymentSchedule($propertyInvestorId, $property){
        $deadline = Deadlines::find($property->deadlines_id);
        
        if (!$deadline) {
            throw new \Exception('No se encontró el plazo especificado');
        }

        $capital = $property->valor_estimado;
        $tem = $property->tem;
        $temConIgv = $tem * 1.18;
        $temDecimal = $temConIgv / 100;
        $n = $deadline->duracion_meses;

        $cuotaMensual = $capital * ($temDecimal * pow(1 + $temDecimal, $n)) / 
                        (pow(1 + $temDecimal, $n) - 1);

        $saldo = $capital;
        $fechaVencimiento = Carbon::now()->addMonth();

        for ($i = 1; $i <= $n; $i++) {
            $intereses = $saldo * $temDecimal;
            $capitalAmortizado = $cuotaMensual - $intereses;
            $saldoFinal = $saldo - $capitalAmortizado;
            
            $igv = $intereses * 0.18;
            $cuotaNeta = $cuotaMensual;
            $totalCuota = $cuotaNeta + $igv;

            PaymentSchedule::create([
                'property_investor_id' => $propertyInvestorId,
                'cuota' => $i,
                'vencimiento' => $fechaVencimiento->format('Y-m-d'),
                'saldo_inicial' => round($saldo, 2),
                'capital' => round($capitalAmortizado, 2),
                'intereses' => round($intereses, 2),
                'cuota_neta' => round($cuotaNeta, 2),
                'igv' => round($igv, 2),
                'total_cuota' => round($totalCuota, 2),
                'saldo_final' => round($saldoFinal, 2),
                'estado' => 'pendiente',
            ]);

            $saldo = $saldoFinal;
            $fechaVencimiento->addMonth();
        }
    }
    public function subastadas(Request $request){
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search', '');
            $ordenMonto = $request->input('orden_monto', 'desc'); // 'asc' o 'desc'
            $ahora = Carbon::now();
            
            $propertyIdsConSubastasActivas = Auction::where('estado', 'activa')
                ->where(function ($query) use ($ahora) {
                    $query->where('dia_subasta', '>', $ahora->toDateString())
                        ->orWhere(function ($q) use ($ahora) {
                            $q->where('dia_subasta', '=', $ahora->toDateString())
                                ->where('hora_fin', '>', $ahora->toTimeString());
                        });
                })
                ->pluck('property_id');
            
            $query = app(Pipeline::class)
                ->send(Property::where('estado', 'en_subasta')
                    ->whereIn('id', $propertyIdsConSubastasActivas)
                    ->with(['subasta', 'currency', 'plazo'])
                )
                ->through([
                    new FilterBySearch($search),
                ])
                ->thenReturn();            
            $collection = PropertyOnliene::collection($query->paginate($perPage));
            $collection->additional(['orden_monto' => $ordenMonto]);
            return $collection;
            
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al cargar las propiedades en subasta',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
    private function handleImagenesUpload(StorePropertyRequest $request, string $propertyId): void {
        $directory = public_path("Propiedades/{$propertyId}");
        File::ensureDirectoryExists($directory, 0755, true);
        foreach ($request->file('imagenes') as $file) {
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->move($directory, $filename);
            Imagenes::create([
                'property_id' => $propertyId,
                'imagen' => $filename,
            ]);
        }
    }
}
