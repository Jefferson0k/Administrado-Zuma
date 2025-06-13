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
            $ahora = Carbon::now();
            $fin = $ahora->copy()->addMinutes(5);
            $duracion = $fin->diffAsCarbonInterval($ahora);

            $property->subasta()->create([
                'monto_inicial' => 20.00,
                'dia_subasta' => $ahora->toDateString(),
                'hora_inicio' => $ahora->toTimeString(),
                'hora_fin' => $fin->toTimeString(),
                'tiempo_finalizacion' => $fin,
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

            $query = app(Pipeline::class)
                ->send(Property::where('estado', 'en_subasta'))
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
