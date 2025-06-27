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
use App\Models\Imagenes;
use App\Models\Property;
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

            // Si no existe subasta, la crea
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

            // Se actualiza el estado y el plazo
            $property->estado = $nuevoEstado;
            $property->deadlines_id = $request->deadlines_id;
            $property->save();

            return response()->json([
                'message' => 'Estado actualizado correctamente.',
                'property' => $property,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function subastadas(Request $request){
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search', '');

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
                    ->whereIn('id', $propertyIdsConSubastasActivas))
                ->through([
                    new FilterBySearch($search),
                ])
                ->thenReturn();

            return PropertyOnliene::collection($query->paginate($perPage));
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al cargar las propiedades en subasta',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
    private function handleImagenesUpload(StorePropertyRequest $request, int $propertyId): void{
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
