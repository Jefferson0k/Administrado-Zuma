<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Property\PropertyUpdateRequest;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Requests\Property\UpdatePropertyRequest;
use App\Http\Resources\PropertyConfiguracionSubastaResource;
use App\Http\Resources\Subastas\Solicitud\SolicitudResource;
use App\Http\Resources\Subastas\Property\PropertyConfiguracionResource;
use App\Http\Resources\Subastas\Property\PropertyReglaResource;
use App\Http\Resources\Subastas\Property\PropertyResource;
use App\Http\Resources\Subastas\Property\PropertyShowResource;
use App\Http\Resources\Subastas\Property\PropertyUpdateResource;
use App\Mail\MasiveEmail;
use App\Models\Auction;
use App\Models\Currency;
use App\Models\Investor;
use App\Models\PaymentSchedule;
use App\Models\Property;
use App\Models\PropertyConfiguracion;
use App\Models\PropertyInvestor;
use App\Models\Solicitud;
use App\Pipelines\FilterByCurrency;
use App\Pipelines\FilterByEstado;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use App\Pipelines\FilterBySearch;
use App\Services\CreditSimulationAmericanoService;
use App\Services\CreditSimulationService;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class PropertyControllers extends Controller{
    public function store(StorePropertyRequest $request)
    {
        Gate::authorize('create', Property::class);
        
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $investor = Investor::findOrFail($data['investor_id']);
            
            // Actualizar tipo de inversor si es necesario
            if ($investor->type !== 'cliente') {
                $investor->type = 'mixto';
                $investor->save();
            }
            
            $solicitud = Solicitud::create([
                'codigo'          => $data['codigo'],
                'investor_id'     => $investor->id,
                'valor_general'   => $data['valor_general'],
                'valor_requerido' => $data['valor_requerido'],
                'currency_id'     => $data['currency_id'],
                'fuente_ingreso'     => $data['fuente_ingreso'],
                'profesion_ocupacion'     => $data['profesion_ocupacion'],
                'ingreso_promedio'     => $data['ingreso_promedio'],

            ]);


            $properties = [];
            foreach ($data['properties'] as $propData) {
                // Crear la propiedad - los mutators convertirán los valores a centavos
                $property = Property::create([
                    'solicitud_id' => $solicitud->id,
                    'nombre' => $propData['nombre'],
                    'direccion' => $propData['direccion'],
                    'departamento' => $propData['departamento'],
                    'provincia' => $propData['provincia'],
                    'distrito' => $propData['distrito'],
                    'descripcion' => $propData['descripcion'] ?? null,
                    'pertenece' => $propData['pertenece'] ?? null,
                    'id_tipo_inmueble' => $propData['id_tipo_inmueble'],
                    'valor_estimado' => $propData['valor_estimado'], // Mutator convierte a centavos
                    // Los campos departamento_id, provincia_id, distrito_id no existen en tu modelo Property
                    // Si los necesitas, deberías agregarlos al modelo o guardarlos de otra forma
                    'estado' => 'pendiente', // o el estado por defecto
                    'created_by' => Auth::id(),
                ]);
                
                // Procesar imágenes
                if (!empty($propData['imagenes'])) {
                    $this->processPropertyImages(
                        $propData['imagenes'],
                        $property,
                        $propData['description'] ?? []
                    );
                }
                
                $properties[] = $property->load(['images', 'solicitud.currency']);
            }
            
            return response()->json([
                'success'    => true,
                'message'    => 'Solicitud registrada exitosamente.',
                'solicitud'  => $solicitud->load(['properties', 'currency']),
                'properties' => $properties,
            ], 201);
        });
    }
    private function processPropertyImages(array $imagenes, $property, array $descripciones = [])
    {
        $disk = Storage::disk('s3');

        foreach ($imagenes as $index => $imagen) {
            if (!$imagen->isValid()) {
                Log::error('Archivo de imagen inválido', [
                    'index' => $index,
                    'error_code' => $imagen->getError(),
                    'original_name' => $imagen->getClientOriginalName()
                ]);
                continue;
            }

            $filename = Str::uuid() . '.' . $imagen->getClientOriginalExtension();
            $path = "propiedades/{$property->id}/{$filename}";

            try {
                $uploadResult = $disk->putFileAs("propiedades/{$property->id}", $imagen, $filename);

                if ($uploadResult) {
                    $property->images()->create([
                        'imagen' => $filename,
                        'path' => $path,
                        'description' => $descripciones[$index] ?? null,
                        'created_by' => Auth::id(),
                    ]);
                }
            } catch (Exception $e) {
                Log::error('Error al subir imagen', [
                    'error' => $e->getMessage(),
                    'property_id' => $property->id,
                    'filename' => $filename,
                ]);
            }
        }
    }

    public function showProperty(int $solicitudId){
        $solicitud = Solicitud::with([
            'investor',
            'currency',
            'properties' => function ($query) {
                $query->with(['images', 'tipoInmueble']);
            }
        ])->findOrFail($solicitudId);
        return response()->json([
            'solicitud' => [
                'id' => $solicitud->id,
                'codigo' => $solicitud->codigo,
                'currency_id' => $solicitud->currency_id,
                'investor' => [
                    'id' => $solicitud->investor->id,
                    'nombre' => $solicitud->investor->nombre_completo ?? $solicitud->investor->name,
                    'documento' => $solicitud->investor->numero_documento ?? $solicitud->investor->document,
                ],
                'valor_general' => $solicitud->valor_general->getAmount() / 100,
                'valor_requerido' => $solicitud->valor_requerido->getAmount() / 100,
                'currency' => $solicitud->currency->codigo,
                'estado' => $solicitud->estado,
                'created_at' => $solicitud->created_at?->format('Y-m-d H:i:s'),
            ],
            'properties' => PropertyResource::collection($solicitud->properties)
        ]);
    }
    public function delete(string $id)
    
    {
        $property = Property::findOrFail($id);
        Gate::authorize('delete', $property);
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
public function updateProperty(UpdatePropertyRequest $request, string $solicitudId)
{
    $solicitud = Solicitud::with('properties.images')->findOrFail($solicitudId);    

    return DB::transaction(function () use ($request, $solicitud) {
        $data = $request->validated();

        // --- Normalizar valor_requerido a entero ---
        $valorRequerido = preg_replace('/[^\d]/', '', $data['valor_requerido']);
        $valorRequerido = (int) $valorRequerido;

        // Actualizar solicitud
        $solicitud->update([
            'valor_requerido' => $valorRequerido,
            'currency_id'     => $data['currency_id'],
        ]);

        // IDs de propiedades que vienen en el request
        $propiedadesEnviadas = collect($data['properties'])
            ->pluck('id')
            ->filter()
            ->toArray();

        // Eliminar propiedades que ya no están en el request
        $solicitud->properties()
            ->whereNotIn('id', $propiedadesEnviadas)
            ->each(function ($property) {
                // Eliminar imágenes de S3
                $property->images->each(function ($image) {
                    if ($image->path && Storage::disk('s3')->exists($image->path)) {
                        Storage::disk('s3')->delete($image->path);
                    }
                    $image->delete();
                });

                $property->delete();
            });

        $updatedProperties = [];

        foreach ($data['properties'] as $propData) {
            // --- Normalizar valor_estimado a entero ---
            $valorEstimado = preg_replace('/[^\d]/', '', $propData['valor_estimado']);
            $valorEstimado = (int) $valorEstimado;

            if (!empty($propData['id'])) {
                // Actualizar propiedad existente
                $property = Property::findOrFail($propData['id']);
                Gate::authorize('update', $property);

                $property->update([
                    'nombre'           => $propData['nombre'],
                    'direccion'        => $propData['direccion'],
                    'departamento'     => $propData['departamento'],
                    'provincia'        => $propData['provincia'],
                    'distrito'         => $propData['distrito'],
                    'descripcion'      => $propData['descripcion'] ?? null,
                    'pertenece'        => $propData['pertenece'] ?? null,
                    'id_tipo_inmueble' => $propData['id_tipo_inmueble'],
                    'valor_estimado'   => $valorEstimado,
                ]);
            } else {
                // Crear nueva propiedad
                $property = Property::create([
                    'solicitud_id'     => $solicitud->id,
                    'nombre'           => $propData['nombre'],
                    'direccion'        => $propData['direccion'],
                    'departamento'     => $propData['departamento'],
                    'provincia'        => $propData['provincia'],
                    'distrito'         => $propData['distrito'],
                    'descripcion'      => $propData['descripcion'] ?? null,
                    'pertenece'        => $propData['pertenece'] ?? null,
                    'id_tipo_inmueble' => $propData['id_tipo_inmueble'],
                    'valor_estimado'   => $valorEstimado,
                    'estado'           => 'pendiente',
                    'created_by'       => Auth::id(),
                ]);
            }

            // Eliminar imágenes marcadas para eliminar
            if (!empty($propData['imagenes_eliminar'])) {
                $disk = Storage::disk('s3');

                foreach ($propData['imagenes_eliminar'] as $url) {
                    $filename = basename($url);
                    $image = $property->images()->where('imagen', $filename)->first();

                    if ($image) {
                        if ($image->path && $disk->exists($image->path)) {
                            $disk->delete($image->path);
                        }

                        $image->deleted_by = Auth::id();
                        $image->save();
                        $image->delete();
                    }
                }
            }

            // Subir nuevas imágenes
            if (!empty($propData['imagenes'])) {
                $this->processPropertyImages(
                    $propData['imagenes'],
                    $property,
                    $propData['descriptions'] ?? []
                );
            }

            $updatedProperties[] = $property->load('images');
        }

        return response()->json([
            'success'    => true,
            'message'    => 'Solicitud y propiedades actualizadas correctamente.',
            'solicitud'  => $solicitud->fresh()->load(['properties.images', 'currency']),
            'properties' => $updatedProperties,
        ], 200);
    });
}


    public function index(Request $request){
        Gate::authorize('viewAny', Property::class);
        $perPage = (int) $request->input('per_page', 10);
        $solicitudes = Solicitud::with(['currency', 'investor', 'properties'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
        return SolicitudResource::collection($solicitudes)
            ->additional([
                'total' => $solicitudes->total(),
            ]);
    }
    public function indexSubastaTotoal(Request $request)
    {
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
        } catch (Throwable $th) {
            return response()->json([
                'message' => 'Error al cargar los datos',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
    /*public function show($id){
        $property = Property::with('subasta')->find($id);
        if (!$property) {
            return response()->json(['error' => 'Propiedad no encontrada'], 404);
        }
        return new PropertyUpdateResource($property);
    }*/
    public function show($id){
        $solicitud = Solicitud::with(['currency', 'investor', 'properties.images'])->find($id);
        if (!$solicitud) {
            return response()->json(['error' => 'Solicitud no encontrada'], 404);
        }
        return new SolicitudResource($solicitud);
    }

    public function enviar(Request $request)
    
    {
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
                } catch (Exception $e) {
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
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el envío: ' . $e->getMessage()
            ], 500);
        }
    }
    private function processEmails($emailsString)
    
    {
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
    public function showCustumer($id)
    
    {
        $config = PropertyConfiguracion::with('plazo')->find($id);
        if (!$config) {
            return response()->json(['error' => 'Configuración no encontrada'], 404);
        }
        return new PropertyShowResource($config);
    }
    public function update(PropertyUpdateRequest $request, $id)
{
    DB::beginTransaction();

    try {
        $solicitud = Solicitud::with('currency')->findOrFail($id);
        Gate::authorize('update', new PropertyConfiguracion());

        $userId = Auth::id();

        // Validar TEM y TEA
        if (!$request->filled('tea') || !$request->filled('tem')) {
            return response()->json([
                'message' => 'TEM y TEA son requeridos',
                'errors' => [
                    'tea' => !$request->filled('tea') ? ['El campo TEA es requerido'] : [],
                    'tem' => !$request->filled('tem') ? ['El campo TEM es requerido'] : [],
                ]
            ], 422);
        }

        // Verificar si ya existe configuración para este estado
        $existingConfig = PropertyConfiguracion::where('solicitud_id', $solicitud->id)
            ->where('estado', $request->estado_configuracion)
            ->latest()
            ->first();

        $tea_entero = (int) round((float) $request->tea * 100);
        $tem_entero = (int) round((float) $request->tem * 100);

        // Crear o actualizar configuración
        if (!$existingConfig) {
            $config = PropertyConfiguracion::create([
                'solicitud_id' => $solicitud->id,
                'deadlines_id' => $request->deadlines_id,
                'tea' => $tea_entero,
                'tem' => $tem_entero,
                'tipo_cronograma' => $request->tipo_cronograma,
                'riesgo' => $request->filled('riesgo') ? $request->riesgo : '-',
                'estado' => $request->estado_configuracion,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
            $solicitud->increment('config_total');
        } else {
            $existingConfig->update([
                'deadlines_id' => $request->deadlines_id,
                'tea' => $tea_entero,
                'tem' => $tem_entero,
                'tipo_cronograma' => $request->tipo_cronograma,
                'riesgo' => $request->filled('riesgo') ? $request->riesgo : '-',
                'updated_by' => $userId,
            ]);
            $config = $existingConfig;
        }

        $config->load('plazo', 'solicitud');

        $valorRequeridoMoney = $solicitud->valor_requerido;
        $valorRequeridoCentavos = $valorRequeridoMoney->getAmount();

        // Buscar inversor existente
        $existingInvestor = PropertyInvestor::where('solicitud_id', $solicitud->id)
            ->where('config_id', $config->id)
            ->first();

        if ($existingInvestor) {
            $existingInvestor->update([
                'amount' => $valorRequeridoCentavos,
                'status' => 'pendiente',
                'updated_by' => $userId,
            ]);
            PaymentSchedule::where('property_investor_id', $existingInvestor->id)->delete();
            $solicitudInvestor = $existingInvestor;
        } else {
            $solicitudInvestor = PropertyInvestor::create([
                'solicitud_id' => $solicitud->id,
                'investor_id' => null,
                'config_id' => $config->id,
                'amount' => $valorRequeridoCentavos,
                'status' => 'pendiente',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }

        // Generar cronograma según tipo de configuración
        $this->generatePaymentScheduleByType($solicitudInvestor->id, $config);

        // Actualizar estado si tiene al menos 2 configuraciones
        if ($solicitud->config_total >= 2 && $solicitud->estado !== 'completo') {
            $solicitud->update([
                'estado' => 'completo',
                'updated_by' => $userId,
            ]);
        }

        DB::commit();

        return response()->json([
            'message' => 'Configuración registrada correctamente.',
            'solicitud' => $solicitud->fresh()->load('currency'),
            'property_investor_id' => $solicitudInvestor->id,
        ]);
    } catch (Exception $e) {
        DB::rollBack();

        return response()->json([
            'message' => 'Error interno del servidor',
            'error' => $e->getMessage(),
        ], 500);
    }
}

private function generatePaymentScheduleByType($solicitudInvestorId, $config)
{
    $deadline = $config->plazo;
    if (!$deadline) {
        throw new Exception('No se encontró configuración o plazo asociado a la solicitud.');
    }

    $solicitud = $config->solicitud;
    $solicitud->tem = $config->tem;
    $solicitud->tea = $config->tea;
    $solicitud->tipo_cronograma = $config->tipo_cronograma;

    $service = $solicitud->tipo_cronograma === 'americano'
        ? new CreditSimulationAmericanoService()
        : new CreditSimulationService();

    $simulation = $service->generate($solicitud, $deadline, 1, $deadline->duracion_meses);

    if (!isset($simulation['cronograma_final']['pagos']) || !is_array($simulation['cronograma_final']['pagos'])) {
        throw new Exception('La simulación no generó un cronograma válido.');
    }

    foreach ($simulation['cronograma_final']['pagos'] as $pago) {
        try {
            $fechaVencimiento = Carbon::createFromFormat('d/m/Y', $pago['vcmto']);
            PaymentSchedule::create([
                'property_investor_id' => $solicitudInvestorId,
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
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        } catch (Exception $e) {
            Log::error("Error creando pago {$pago['cuota']}: " . $e->getMessage(), [
                'pago' => $pago,
                'property_investor_id' => $solicitudInvestorId
            ]);
            throw new Exception("Error procesando la cuota {$pago['cuota']}: " . $e->getMessage());
        }
    }
}

private function cleanNumericValue($value): float
{
    if ($value === null || $value === '') return 0.0;
    if (is_string($value)) $value = preg_replace('/[^0-9.-]/', '', $value);
    return (float) $value;
}

    public function subastadas(Request $request)
    
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search', '');
            $ordenMonto = $request->input('orden_monto', 'desc');
            $ahora = now();

            $propertyIdsConSubastasActivas = Auction::where('estado', 'en_subasta')
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
        } catch (Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar configuraciones en subasta',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
    public function listProperties(Request $request){
        try {
            Gate::authorize('viewAny', Property::class);

            $perPage = $request->input('per_page', 10);
            $search = $request->input('search');

            $query = Solicitud::query()
                ->where('estado', 'pendiente')
                ->when($search, function ($query) use ($search) {
                    return $query->where(function ($q) use ($search) {
                        $q->where('codigo', 'LIKE', "%{$search}%")
                            ->orWhere('id', 'LIKE', "%{$search}%")
                            ->orWhereHas('properties', function ($p) use ($search) {
                                $p->where('nombre', 'LIKE', "%{$search}%")
                                    ->orWhere('departamento', 'LIKE', "%{$search}%")
                                    ->orWhere('provincia', 'LIKE', "%{$search}%")
                                    ->orWhere('distrito', 'LIKE', "%{$search}%");
                            });
                    });
                })
                ->with(['currency', 'properties.images']); // Incluye currency y propiedades con imágenes

            $solicitudes = $query->paginate($perPage);

            return response()->json([
                'data' => $solicitudes->map(function ($solicitud) {
                    return [
                        'id'              => $solicitud->id,
                        'codigo'          => $solicitud->codigo,
                        'estado'          => $solicitud->estado,
                        'valor_general'   => $solicitud->valor_general
                            ? (float) $solicitud->valor_general->getAmount() / 100
                            : null,
                        'valor_requerido' => $solicitud->valor_requerido
                            ? (float) $solicitud->valor_requerido->getAmount() / 100
                            : null,
                        'currency_id'     => $solicitud->currency_id,
                        'currency'        => $solicitud->currency?->codigo ?? 'PEN',
                        'currency_symbol' => $solicitud->currency_id === 1 ? 'S/' : '$',
                    ];
                }),
                'pagination' => [
                    'total'        => $solicitudes->total(),
                    'current_page' => $solicitudes->currentPage(),
                    'per_page'     => $solicitudes->perPage(),
                    'last_page'    => $solicitudes->lastPage(),
                    'from'         => $solicitudes->firstItem(),
                    'to'           => $solicitudes->lastItem(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error al listar solicitudes',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function listPropertiesActivas(Request $request)
{
    try {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search', '');

        $query = PropertyConfiguracion::with(['solicitud.currency', 'solicitud.investor'])
            ->where('estado', 2) // estado de la configuración
            ->whereHas('solicitud', function ($q) use ($search) {
                // Filtrar solicitudes con estados válidos
                $q->whereIn('estado', ['completo', 'desactivada']);

                if ($search) {
                    $q->where(function ($subquery) use ($search) {
                        $subquery->where('codigo', 'LIKE', "%{$search}%")
                            ->orWhereHas('investor', function ($qqq) use ($search) {
                                $qqq->where('name', 'LIKE', "%{$search}%")
                                    ->orWhere('first_last_name', 'LIKE', "%{$search}%")
                                    ->orWhere('second_last_name', 'LIKE', "%{$search}%")
                                    ->orWhere('document', 'LIKE', "%{$search}%");
                            });
                    });
                }
            });

        $configuraciones = $query->paginate($perPage);

        return response()->json([
            'data' => $configuraciones->map(function ($config) {
                $solicitud = $config->solicitud;
                $investor = $solicitud->investor ?? null;

                return [
                    'config_id' => $config->id,
                    'solicitud_id' => $solicitud->id ?? null,
                    'codigo_solicitud' => $solicitud->codigo ?? null,
                    'estado_config' => $config->estado,
                    'estado_solicitud' => $solicitud->estado ?? null,

                    'valor_general' => $solicitud->valor_general 
                        ? number_format($solicitud->valor_general->getAmount() / 100, 2, '.', '') 
                        : null,

                    'valor_requerido' => $solicitud->valor_requerido 
                        ? number_format($solicitud->valor_requerido->getAmount() / 100, 2, '.', '') 
                        : null,
                    'moneda' => $solicitud->currency->codigo ?? null,
                    'investor_id' => $investor->id ?? null,
                    'investor_name' => $investor->name ?? null,
                    'investor_first_last_name' => $investor->first_last_name ?? null,
                    'investor_second_last_name' => $investor->second_last_name ?? null,
                    'investor_document' => $investor->document ?? null,
                    'tea' => $config->tea,
                    'tem' => $config->tem,
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
        try {
            Gate::authorize('viewAny', PropertyConfiguracion::class);

            $perPage   = (int) $request->get('per_page', 10);
            $search    = trim((string) $request->get('search', ''));
            $sortField = $request->input('sort_field');
            $sortOrder = strtolower($request->input('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';

            $pcTable  = (new PropertyConfiguracion)->getTable();
            $solTable = (new Solicitud)->getTable();

            $sortableMap = [
                'codigo'          => "{$solTable}.codigo",
                'Moneda'          => "currencies.codigo",
                'valor_general'   => "{$solTable}.valor_general",
                'valor_requerido' => "{$solTable}.valor_requerido",
                'estado'          => "{$pcTable}.estado",
                'created_at'      => "{$pcTable}.created_at",
                'updated_at'      => "{$pcTable}.updated_at",
            ];

            // Tomar estado del request, si no hay, por defecto 1
            $estadoRequest = $request->get('estado', 1);

            $query = PropertyConfiguracion::query()
                ->when($estadoRequest, function ($q, $estado) {
                    // Si es un solo valor
                    if (is_numeric($estado)) {
                        $q->where('estado', $estado);
                    } 
                    // Si viene un array o coma separados
                    elseif (is_string($estado) && str_contains($estado, ',')) {
                        $estados = explode(',', $estado);
                        $q->whereIn('estado', $estados);
                    }
                })
                ->with(['solicitud.currency', 'solicitud.investor']);

            // Búsqueda
            if ($search !== '') {
                $query->where(function ($q) use ($search, $solTable) {
                    $q->whereHas('solicitud', function($qq) use ($search) {
                        $qq->where('codigo', 'like', "%{$search}%")
                        ->orWhereHas('investor', function ($qqq) use ($search) {
                            $qqq->where('nombre', 'like', "%{$search}%");
                        });
                    });
                });
            }

            // Join con currencies si se ordena por moneda
            $needsCurrencyJoin = $sortField && isset($sortableMap[$sortField]) && str_starts_with($sortableMap[$sortField], 'currencies.');
            if ($needsCurrencyJoin) {
                $query->leftJoin('solicitudes', "{$pcTable}.solicitud_id", '=', 'solicitudes.id')
                    ->leftJoin('currencies', 'solicitudes.currency_id', '=', 'currencies.id')
                    ->select("{$pcTable}.*");
            }

            // Ordenamiento
            if ($sortField && isset($sortableMap[$sortField])) {
                $query->orderBy($sortableMap[$sortField], $sortOrder);
            } else {
                $query->orderBy("{$pcTable}.created_at", 'desc');
            }

            $configuraciones = $query->paginate($perPage);

            return PropertyConfiguracionResource::collection($configuraciones);

        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para ver las reglas.'], 403);
        }
    }

    public function showReglas($id)
    
    {
        $regla = PropertyConfiguracion::find($id);
        if (!$regla) {
            return response()->json(['message' => 'Configuración no encontrada'], 404);
        }
        Gate::authorize('view', $regla);
        return new PropertyReglaResource($regla);
    }

    public function showConfig($configId, Request $request)
    
    {
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
