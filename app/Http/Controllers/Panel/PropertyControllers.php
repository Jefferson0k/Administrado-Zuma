<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Property\PropertyUpdateRequest;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Resources\PropertyConfiguracionSubastaResource;
use App\Http\Resources\Subastas\Property\PropertyConfiguracionResource;
use App\Http\Resources\Subastas\Property\PropertyReglaResource;
use App\Http\Resources\Subastas\Property\PropertyResource;
use App\Http\Resources\Subastas\Property\PropertyShowResource;
use App\Http\Resources\Subastas\Property\PropertyUpdateResource;
use App\Mail\MasiveEmail;
use App\Models\Auction;
use App\Models\Currency;
use App\Models\PaymentSchedule;
use App\Models\Property;
use App\Models\PropertyConfiguracion;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Money\Money;
use Money\Currency as MoneyCurrency;
class PropertyControllers extends Controller{
    public function store(StorePropertyRequest $request)
    {
        $data = $request->validated();
        
        // Asegurar que currency_id existe
        if (!$data['currency_id']) {
            $data['currency_id'] = Currency::where('codigo', 'PEN')->first()->id;
        }
        
        $property = Property::create($data);
        
        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $imagen) {
                $filename = Str::uuid() . '.' . $imagen->getClientOriginalExtension();
                $path = "propiedades/{$property->id}/{$filename}";
                Storage::disk('s3')->put($path, file_get_contents($imagen));
                $property->images()->create([
                    'imagen' => $filename,
                    'path'   => $path,
                ]);
            }
        }

        // Refrescar desde BD para asegurar que tenemos todos los datos
        $property = Property::with(['images', 'currency'])->find($property->id);
        
        return response()->json([
            'success'  => true,
            'message'  => 'Propiedad registrada exitosamente.',
            'property' => $property,
        ], 201);
    }
    public function showProperty(string $id){
        $property = Property::with(['currency', 'images'])->findOrFail($id);
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
                    $imagePath = "propiedades/{$property->id}/{$image->imagen}";
                    if (Storage::disk('s3')->exists($imagePath)) {
                        Storage::disk('s3')->delete($imagePath);
                    }
                    $image->delete();
                }
            }
        }
        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $imagen) {
                $filename = Str::uuid() . '.' . $imagen->getClientOriginalExtension();
                $path = "propiedades/{$property->id}/{$filename}";
                Storage::disk('s3')->put($path, file_get_contents($imagen));
                $property->images()->create([
                    'imagen' => $filename,
                    'path'   => $path,
                ]);
            }
        }
        return response()->json([
            'success' => true,
            'message' => 'Propiedad actualizada correctamente.'
        ], 200);
    }
    public function delete(string $id){
        $property = Property::findOrFail($id);
        foreach ($property->images as $image) {
            $imagePath = "propiedades/{$property->id}/{$image->imagen}";
            if (Storage::disk('s3')->exists($imagePath)) {
                Storage::disk('s3')->delete($imagePath);
            }
            $image->delete();
        }
        $directory = "propiedades/{$property->id}";
        Storage::disk('s3')->deleteDirectory($directory);
        $property->delete();
        return response()->json([
            'success' => true,
            'message' => 'Propiedad eliminada correctamente.'
        ], 200);
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
                ->send(
                    PropertyConfiguracion::with([
                        'property.currency',
                        'plazo'
                    ])
                    ->where('estado', 1)
                    ->whereHas('property', function ($q) {
                        $q->where('estado', 'activa');
                    })
                )
                ->through([
                    new FilterBySearch($search),
                    new FilterByCurrency($currencyId),
                ])
                ->thenReturn();

            return PropertyConfiguracionResource::collection($query->paginate($perPage));
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
    public function enviar(Request $request){
        $validator = Validator::make($request->all(), [
            'emails' => 'required|string',
            'mensaje' => 'required|string|min:10',
            'asunto' => 'required|string|max:255',
            'investor_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $emailsArray = $this->processEmails($request->emails);
            if (empty($emailsArray)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron emails válidos'
                ], 400);
            }

            $mensaje = $request->mensaje;
            $asunto = $request->asunto;
            $investorId = $request->investor_id;
            
            $enviados = 0;
            $errores = [];

            foreach ($emailsArray as $email) {
                try {
                    Mail::to($email)->send(new MasiveEmail($mensaje, $asunto, $investorId));
                    $enviados++;
                } catch (\Exception $e) {
                    $errores[] = "Error enviando a {$email}: " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Proceso completado. {$enviados} emails enviados exitosamente",
                'enviados' => $enviados,
                'total' => count($emailsArray),
                'errores' => $errores
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el envío: ' . $e->getMessage()
            ], 500);
        }
    }
    private function processEmails($emailsString){
        $emails = preg_split('/[,;\s\n\r]+/', $emailsString);
        $validEmails = [];
        foreach ($emails as $email) {
            $email = trim($email);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $validEmails[] = $email;
            }
        }
        return array_unique($validEmails);
    }
    public function showCustumer($id){
        $config = PropertyConfiguracion::with('plazo')->find($id);
        if (!$config) {
            return response()->json(['error' => 'Configuración no encontrada'], 404);
        }
        return new PropertyShowResource($config);
    }
    public function update(PropertyUpdateRequest $request, $id){
        DB::beginTransaction();
        try {
            $property = Property::with('currency')->findOrFail($id);
            if (!$request->filled('tea') || !$request->filled('tem')) {
                return response()->json([
                    'message' => 'TEM y TEA son requeridos',
                    'errors' => [
                        'tea' => !$request->filled('tea') ? ['El campo TEA es requerido'] : [],
                        'tem' => !$request->filled('tem') ? ['El campo TEM es requerido'] : [],
                    ]
                ], 422);
            }
            $existingConfig = PropertyConfiguracion::where('property_id', $property->id)
                ->where('estado', $request->estado_configuracion)
                ->latest()
                ->first();
            if (!$existingConfig) {
                $config = PropertyConfiguracion::create([
                    'property_id' => $property->id,
                    'deadlines_id' => $request->deadlines_id,
                    'tea' => $request->tea,
                    'tem' => $request->tem,
                    'tipo_cronograma' => $request->tipo_cronograma,
                    'riesgo' => $request->riesgo,
                    'estado' => $request->estado_configuracion,
                ]);
                $property->increment('config_total');
            } else {
                $existingConfig->update([
                    'deadlines_id' => $request->deadlines_id,
                    'tea' => $request->tea,
                    'tem' => $request->tem,
                    'tipo_cronograma' => $request->tipo_cronograma,
                    'riesgo' => $request->riesgo,
                ]);
                $config = $existingConfig;
            }
            $config->load(['plazo', 'property']);
            $valorRequeridoMoney = $property->valor_requerido;
            $valorRequeridoCentavos = $valorRequeridoMoney->getAmount(); // Ya está en centavos
            $existingInvestor = PropertyInvestor::where('property_id', $property->id)
                ->where('config_id', $config->id)
                ->first();

            if ($existingInvestor) {
                $existingInvestor->update([
                    'amount' => $valorRequeridoCentavos, // Guardar en centavos
                    'status' => 'pendiente',
                ]);

                // Eliminar cronograma anterior
                PaymentSchedule::where('property_investor_id', $existingInvestor->id)->delete();
                $propertyInvestor = $existingInvestor;
            } else {
                $propertyInvestor = PropertyInvestor::create([
                    'property_id' => $property->id,
                    'investor_id' => null,
                    'config_id' => $config->id,
                    'amount' => $valorRequeridoCentavos, // Guardar en centavos
                    'status' => 'pendiente',
                ]);
            }

            // Generar cronograma
            $this->generatePaymentScheduleByType($propertyInvestor->id, $config);

            // Si ya tiene 2 configuraciones distintas, cambiar estado a completo
            if ($property->config_total >= 2 && $property->estado !== 'completo') {
                $property->estado = 'completo';
                $property->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Configuración registrada correctamente.',
                'property' => $property->fresh()->load('currency'),
                'property_investor_id' => $propertyInvestor->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    private function generatePaymentScheduleByType($propertyInvestorId, $config){
        $deadline = $config->plazo;
        if (!$deadline) {
            throw new \Exception('No se encontró configuración o plazo asociado a la propiedad.');
        }
        $property = $config->property;
        $property->tem = $config->tem;
        $property->tea = $config->tea;
        $property->tipo_cronograma = $config->tipo_cronograma;
        if ($property->tipo_cronograma === 'americano') {
            $service = new CreditSimulationAmericanoService();
        } else {
            $service = new CreditSimulationService();
        }
        $simulation = $service->generate($property, $deadline, 1, $deadline->duracion_meses);
        if (!isset($simulation['cronograma_final']['pagos']) || !is_array($simulation['cronograma_final']['pagos'])) {
            throw new \Exception('La simulación no generó un cronograma válido.');
        }

        $pagos = $simulation['cronograma_final']['pagos'];
        foreach ($pagos as $pago) {
            try {
                $fechaVencimiento = Carbon::createFromFormat('d/m/Y', $pago['vcmto']);
                PaymentSchedule::create([
                    'property_investor_id' => $propertyInvestorId,
                    'cuota' => (int) $pago['cuota'],
                    'vencimiento' => $fechaVencimiento->format('Y-m-d'),
                    'saldo_inicial' => $this->cleanNumericValue($pago['saldo_inicial']),
                    'capital' => $this->cleanNumericValue($pago['capital']),
                    'intereses' => $this->cleanNumericValue($pago['interes']),
                    'cuota_neta' => $this->cleanNumericValue($pago['cuota_neta']),
                    'igv' => $this->cleanNumericValue($pago['igv'] ?? 0),
                    'total_cuota' => $this->cleanNumericValue($pago['total_cuota']),
                    'saldo_final' => $this->cleanNumericValue($pago['saldo_final']),
                    'estado' => 'pendiente',
                ]);
                
            } catch (\Exception $e) {
                Log::error("Error creando pago {$pago['cuota']}: " . $e->getMessage(), [
                    'pago' => $pago,
                    'property_investor_id' => $propertyInvestorId
                ]);
                throw new \Exception("Error procesando la cuota {$pago['cuota']}: " . $e->getMessage());
            }
        }
    }
    private function cleanNumericValue($value): float{
        if ($value === null || $value === '') {
            return 0.0;
        }
        if (is_string($value)) {
            $cleaned = preg_replace('/[^0-9.-]/', '', $value);
            $value = $cleaned;
        }
        
        return (float) $value;
    }
    public function subastadas(Request $request){
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search', '');
            $ordenMonto = $request->input('orden_monto', 'desc');
            $ahora = now();

            $propertyIdsConSubastasActivas = Auction::where('estado', 'activa')
                ->where(function ($query) use ($ahora) {
                    $query->where('dia_subasta', '>', $ahora->toDateString())
                        ->orWhere(function ($q) use ($ahora) {
                            $q->where('dia_subasta', '=', $ahora->toDateString())
                                ->where('hora_fin', '>', $ahora->toTimeString());
                        });
                })
                ->pluck('property_id');

            $query = PropertyConfiguracion::where('estado', 1)
                ->whereIn('property_id', $propertyIdsConSubastasActivas)
                ->whereHas('property', function ($q) {
                    $q->where('estado', 'en_subasta');
                })
                ->with([
                    'property.currency',
                    'plazo',
                    'property.subasta',
                    'propertyInvestor' // << importante
                ]);

            if (!empty($search)) {
                $query->whereHas('property', function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%");
                });
            }

            $result = $query->orderBy('id', 'desc')->paginate($perPage);

            return PropertyConfiguracionSubastaResource::collection($result)->additional([
                'success' => true,
                'orden_monto' => $ordenMonto,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar configuraciones en subasta',
                'error' => $th->getMessage(),
            ], 500);
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
                ->with(['currency']);
            $properties = $query->paginate($perPage);
            return response()->json([
                'data' => $properties->map(function ($property) {
                    return [
                        'id'             => $property->id,
                        'nombre'         => $property->nombre,
                        'departamento'   => $property->departamento,
                        'provincia'      => $property->provincia,
                        'distrito'       => $property->distrito,
                        'direccion'      => $property->direccion,
                        'descripcion'    => $property->descripcion,
                        'estado'         => $property->estado,
                        'valor_requerido' => $property->valor_requerido 
                            ? (float) $property->valor_requerido->getAmount() / 100 
                            : null,
                        'currency_id'    => $property->currency_id,
                        'currency'       => $property->currency?->codigo ?? 'PEN',
                        'currency_symbol' => $property->currency_id === 1 ? 'S/' : '$',
                    ];
                }),
                'pagination' => [
                    'total'        => $properties->total(),
                    'current_page' => $properties->currentPage(),
                    'per_page'     => $properties->perPage(),
                    'last_page'    => $properties->lastPage(),
                    'from'         => $properties->firstItem(),
                    'to'           => $properties->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al listar propiedades',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function listPropertiesActivas(Request $request){
        try {
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search');
            $query = PropertyConfiguracion::with(['property.currency', 'property.investor'])
                ->where('estado', 2)
                ->whereHas('property', function ($q) use ($search) {
                    $q->whereIn('estado', ['completo', 'desactivada']);

                    if ($search) {
                        $q->where(function ($subquery) use ($search) {
                            $subquery->where('nombre', 'LIKE', "%{$search}%")
                                ->orWhere('id', 'LIKE', "%{$search}%")
                                ->orWhere('departamento', 'LIKE', "%{$search}%")
                                ->orWhere('provincia', 'LIKE', "%{$search}%")
                                ->orWhere('distrito', 'LIKE', "%{$search}%");
                        });
                    }
                });
            $configuraciones = $query->paginate($perPage);
            return response()->json([
                'data' => $configuraciones->map(function ($config) {
                    $property = $config->property;
                    $investor = $property->investor;
                    $valorEstimado = $property->valor_estimado
                        ? [
                            'amount' => $property->valor_estimado->getAmount(),
                            'decimal' => number_format($property->valor_estimado->getAmount() / 100, 2, '.', ''),
                            'currency' => $property->valor_estimado->getCurrency()->getCode(),
                        ]
                        : null;

                    return [
                        'config_id' => $config->id,
                        'property_id' => $property->id,
                        'nombre' => $property->nombre,
                        'departamento' => $property->departamento,
                        'provincia' => $property->provincia,
                        'distrito' => $property->distrito,
                        'direccion' => $property->direccion,
                        'descripcion' => $property->descripcion,
                        'estado_property' => $property->estado,
                        'estado_config' => $config->estado,
                        'valor_estimado' => $valorEstimado,
                        'tea' => $config->tea,
                        'tem' => $config->tem,
                        'moneda' => $property->currency->codigo ?? null,
                        'foto' => $property->getImagenes(),
                        'cliente_id' => $property->investor_id,
                        'investor_name' => $investor->name ?? null,
                        'investor_first_last_name' => $investor->first_last_name ?? null,
                        'investor_second_last_name' => $investor->second_last_name ?? null,
                        'investor_document' => $investor->document ?? null,
                    ];
                }),
                'pagination' => [
                    'total' => $configuraciones->total(),
                    'current_page' => $configuraciones->currentPage(),
                    'per_page' => $configuraciones->perPage(),
                    'last_page' => $configuraciones->lastPage(),
                    'from' => $configuraciones->firstItem(),
                    'to' => $configuraciones->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al listar configuraciones',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function listReglas(Request $request){
        $perPage = $request->get('per_page', 10);
        $estado = $request->get('estado', 1);
        $configuraciones = PropertyConfiguracion::with(['property', 'plazo'])
            ->where('estado', $estado)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
        return PropertyConfiguracionResource::collection($configuraciones);
    }
    public function showReglas($id){
        $regla = PropertyConfiguracion::find($id);
        if (!$regla) {
            return response()->json(['message' => 'Configuración no encontrada'], 404);
        }
        return new PropertyReglaResource($regla);
    }
    public function showConfig($configId, Request $request){
        $perPage = $request->input('per_page', 15);
        $propertyInvestorIds = PropertyInvestor::where('config_id', $configId)->pluck('id');
        if ($propertyInvestorIds->isEmpty()) {
            return response()->json(['error' => 'No se encontraron inversionistas con esa configuración'], 404);
        }
        $schedules = PaymentSchedule::whereIn('property_investor_id', $propertyInvestorIds)
            ->orderBy('property_investor_id')
            ->orderBy('cuota')
            ->paginate($perPage);
        return response()->json($schedules);
    }
}
