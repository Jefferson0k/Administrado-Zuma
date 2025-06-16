<?php 

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Property\EstadoRequest;
use App\Http\Resources\Subastas\Property\PropertyOnliene;
use App\Http\Resources\Subastas\Property\PropertyResource;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use App\Pipelines\FilterBySearch;
use Carbon\Carbon;
use Inertia\Inertia;

class PropertyControllers extends Controller{
    public function index(Request $request){
        try {
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search', '');
            $query = app(Pipeline::class)
                ->send(Property::query())
                ->through([
                    new FilterBySearch($search),
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
        $property = Property::findOrFail($id);
        $resource = new PropertyOnliene($property);

        return Inertia::render('panel/Onlien/Desarrollo/indexDetalleOnlien', [
            'property' => $resource,
        ]);
    }
    public function update(EstadoRequest $request, $id){
        $property = Property::findOrFail($id);
        $nuevoEstado = $request->estado;

        if ($nuevoEstado === 'en_subasta' && !$property->subasta) {
            $diaSubasta = $request->input('dia_subasta');
            $horaInicio = $request->input('hora_inicio');
            $horaFin    = $request->input('hora_fin');

            $fechaInicio = Carbon::createFromFormat('Y-m-d H:i:s', "$diaSubasta $horaInicio");
            $fechaFin    = Carbon::createFromFormat('Y-m-d H:i:s', "$diaSubasta $horaFin");

            if ($fechaFin->lessThanOrEqualTo($fechaInicio)) {
                return response()->json(['message' => 'La hora de fin debe ser mayor a la de inicio'], 422);
            }

            $property->subasta()->create([
                'monto_inicial' => $request->input('monto_inicial'),
                'dia_subasta' => $diaSubasta,
                'hora_inicio' => $horaInicio,
                'hora_fin' => $horaFin,
                'tiempo_finalizacion' => $fechaFin,
                'estado' => 'activa',
            ]);
        }

        $property->estado = $nuevoEstado;
        $property->save();

        return response()->json([
            'message' => 'Estado actualizado correctamente.',
            'property' => $property,
        ]);
    }
    public function subastadas(Request $request){
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search', '');

            $ahora = Carbon::now();

            $propertyIdsConSubastasActivas = \App\Models\Auction::where('estado', 'activa')
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
}
