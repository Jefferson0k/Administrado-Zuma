<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Property\PropertyUpdateRequest;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Resources\Subastas\Property\PropertyOnliene;
use App\Http\Resources\Subastas\Property\PropertyReglaResource;
use App\Http\Resources\Subastas\Property\PropertyReglasResource;
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
use App\Services\CreditSimulationAmericanoService;
use App\Services\CreditSimulationService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
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
        DB::beginTransaction();
        try {
            $property = Property::findOrFail($id);
            $property->estado = $request->estado;
            $property->tea = $request->tea;
            $property->tem = $request->tem;
            $property->deadlines_id = $request->deadlines_id;
            $property->riesgo = $request->riesgo;
            $property->tipo_cronograma = $request->tipo_cronograma;
            $property->save();
            $oldInvestors = PropertyInvestor::where('property_id', $property->id)->get();
            foreach ($oldInvestors as $inv) {
                PaymentSchedule::where('property_investor_id', $inv->id)->delete();
                $inv->delete();
            }
            $propertyInvestor = PropertyInvestor::create([
                'property_id' => $property->id,
                'investor_id' => null,
                'amount' => $property->valor_estimado,
                'status' => 'pendiente',
            ]);

            $this->generatePaymentScheduleByType($propertyInvestor->id, $property);
            DB::commit();
            return response()->json([
                'message' => 'Estado actualizado correctamente y cronograma regenerado.',
                'property' => $property,
                'property_investor_id' => $propertyInvestor->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    private function generatePaymentScheduleByType($propertyInvestorId, $property){
        $deadline = Deadlines::find($property->deadlines_id);
        if (!$deadline) {
            throw new Exception('No se encontró el plazo especificado');
        }
        if ($property->tipo_cronograma === 'americano') {
            $service = new CreditSimulationAmericanoService();
        } else {
            $service = new CreditSimulationService();
        }
        $simulation = $service->generate($property, $deadline, 1, $deadline->duracion_meses);
        $pagos = $simulation['cronograma_final']['pagos'];
        foreach ($pagos as $pago) {
            PaymentSchedule::create([
                'property_investor_id' => $propertyInvestorId,
                'cuota' => $pago['cuota'],
                'vencimiento' => Carbon::createFromFormat('d/m/Y', $pago['vcmto'])->format('Y-m-d'),
                'saldo_inicial' => (float) str_replace(',', '', $pago['saldo_inicial']),
                'capital' => (float) str_replace(',', '', $pago['capital']),
                'intereses' => (float) str_replace(',', '', $pago['interes']),
                'cuota_neta' => (float) str_replace(',', '', $pago['cuota_neta']),
                'igv' => (float) str_replace(',', '', $pago['igv']),
                'total_cuota' => (float) str_replace(',', '', $pago['total_cuota']),
                'saldo_final' => (float) str_replace(',', '', $pago['saldo_final']),
                'estado' => 'pendiente',
            ]);
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
    public function listProperties(Request $request): JsonResponse{
        try {
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search');

            $query = Property::query()
                ->where('estado', 'pendiente')
                ->when($search, function ($query) use ($search) {
                    return $query->where(function ($q) use ($search) {
                        $q->where('nombre', 'LIKE', "%{$search}%")
                        ->orWhere('id', 'LIKE', "%{$search}%")
                        ->orWhere('departamento', 'LIKE', "%{$search}%")
                        ->orWhere('provincia', 'LIKE', "%{$search}%")
                        ->orWhere('distrito', 'LIKE', "%{$search}%");
                    });
                })
                ->with(['currency', 'plazo']);

            $properties = $query->paginate($perPage);

            return response()->json([
                'data' => $properties->map(function ($property) {
                    return [
                        'id' => $property->id,
                        'nombre' => $property->nombre,
                        'departamento' => $property->departamento,
                        'provincia' => $property->provincia,
                        'distrito' => $property->distrito,
                        'direccion' => $property->direccion,
                        'descripcion' => $property->descripcion,
                        'estado' => $property->estado,
                        'valor_estimado' => $property->valor_estimado,
                    ];
                }),
                'pagination' => [
                    'total' => $properties->total(),
                    'current_page' => $properties->currentPage(),
                    'per_page' => $properties->perPage(),
                    'last_page' => $properties->lastPage(),
                    'from' => $properties->firstItem(),
                    'to' => $properties->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al listar propiedades',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function listReglas(Request $request){
        $query = Property::whereIn('estado', ['activa', 'desactivada']);
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', '%' . $search . '%')
                ->orWhere('tipo_cronograma', 'like', '%' . $search . '%')
                ->orWhere('riesgo', 'like', '%' . $search . '%');
            });
        }

        $propiedades = $query->with('plazo')->paginate(15);

        return PropertyReglasResource::collection($propiedades);
    }
    public function showReglas($id){
        $regla = Property::find($id);
        if (!$regla) {
            return response()->json(['message' => 'Propiedad no encontrada'], 404);
        }
        return new PropertyReglaResource($regla);
    }
}
