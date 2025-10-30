<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Invoice\UpdateInvoiceRequest;
use App\Http\Resources\Factoring\Invoice\InvoiceResource;
use App\Models\Invoice;
use App\Pipelines\AmountRangeFilter;
use App\Pipelines\CompanyFilter;
use App\Pipelines\CurrencyFilter;
use App\Pipelines\RateRangeFilter;
use App\Pipelines\SearchInvoiceFilter;
use App\Pipelines\StatusFilter;
use App\Services\InvoiceService;
use App\Exports\InvoicesExport;
use App\Http\Resources\Factoring\Invoice\PaymentsInvoiceResource;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\HistoryAprobadorInvoice;
use Throwable;
use Carbon\Carbon;
use App\Helpers\MoneyConverter;

class InvoiceController extends Controller
{
    private int $codigoCorrelativo = 0;
    public function index(Request $request)
    {
        try {
            Gate::authorize('viewAny', Invoice::class);
            $perPage   = (int) $request->input('per_page', 15);
            $search    = $request->input('search', '');
            $status    = $request->input('status');
            $currency  = $request->input('currency');
            $company   = $request->input('company');
            $minAmount = $request->input('min_amount');
            $maxAmount = $request->input('max_amount');
            $minRate   = $request->input('min_rate');
            $maxRate   = $request->input('max_rate');

            $sortField = $request->input('sort_field');
            $sortOrder = strtolower($request->input('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';

            $query = app(Pipeline::class)
                ->send(Invoice::query()->with(['company']))
                ->through([
                    new SearchInvoiceFilter($search),
                    new StatusFilter($status),
                    new CurrencyFilter($currency),
                    new CompanyFilter($company),
                    new AmountRangeFilter($minAmount, $maxAmount),
                    new RateRangeFilter($minRate, $maxRate),
                ])
                ->thenReturn();

            // Mapeo campos UI -> columnas reales en BD
            $sortableMap = [
                // Relación con company
                'razonSocial'               => 'companies.name',
                'ruc'                       => 'companies.document',
                // Campos de invoices
                'codigo'                    => 'invoices.invoice_code',
                'moneda'                    => 'invoices.currency',
                'montoFactura'              => 'invoices.amount',
                'montoAsumidoZuma'          => 'invoices.financed_amount',
                'montoDisponible'           => DB::raw('(invoices.amount - invoices.financed_amount)'),
                'tasa'                      => 'invoices.rate',
                'fechaPago'                 => 'invoices.estimated_pay_date',
                'fechaCreacion'             => 'invoices.created_at',
                'estado'                    => 'invoices.status',
                'tipo'                      => 'invoices.type',
                'situacion'                 => 'invoices.situation',
                'condicionOportunidadInversion' => 'invoices.investment_opportunity_condition',
                'fechaHoraCierreInversion'  => 'invoices.investment_close_datetime',
                'porcentajeObjetivoTerceros' => 'invoices.third_party_goal_percent',
                'porcentajeInversionTerceros' => 'invoices.third_party_investment_percent',

                // Campos de aprobaciones (primer nivel)
                'PrimerStado'               => 'invoices.approval1_status',
                'userprimer'                => 'invoices.approval1_user',
                'tiempoUno'                 => 'invoices.approval1_time',

                // Campos de aprobaciones (segundo nivel)
                'SegundaStado'              => 'invoices.approval2_status',
                'userdos'                   => 'invoices.approval2_user',
                'tiempoDos'                 => 'invoices.approval2_time',



            ];

            // Asegurar JOIN si el campo viene de companies
            if (in_array($sortField, ['razonSocial', 'ruc'])) {
                $alreadyJoined = collect($query->getQuery()->joins ?? [])->contains(
                    fn($join) => $join->table === 'companies'
                );

                if (!$alreadyJoined) {
                    $query->leftJoin('companies', 'companies.id', '=', 'invoices.company_id');
                }
            }

            // Asegurar select de invoices.* SIEMPRE
            $query->select('invoices.*');

            // Aplicar orden
            $query->reorder();
            if ($sortField && array_key_exists($sortField, $sortableMap)) {
                if ($sortField === 'montoDisponible') {
                    $query->orderByRaw('(invoices.amount - invoices.financed_amount) ' . $sortOrder);
                } else {
                    $query->orderBy($sortableMap[$sortField], $sortOrder);
                }
            } else {
                // Orden por defecto
                $query->orderBy('invoices.created_at', 'desc');
            }

            // Logs debug
            Log::info('Invoice index sorting', compact('sortField', 'sortOrder'));
            Log::info('Invoice index SQL', [
                'sql'      => $query->toSql(),
                'bindings' => $query->getBindings(),
            ]);

            $invoices = $query->paginate($perPage);

            return InvoiceResource::collection($invoices)->additional([
                'total' => $invoices->total(),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'No tienes permiso para ver las facturas.'
            ], 403);
        } catch (Throwable $e) {
            Log::error('Error al listar las facturas: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Error al listar las facturas.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function indexfilter()
    {
        try {
            Gate::authorize('viewAny', Invoice::class);

            $allowedStatus = ['active', 'expired', 'judicialized', 'reprogramed', 'daStandby'];

            $invoices = Invoice::whereIn('status', $allowedStatus)
                ->orderBy('created_at', 'desc') // 🔽 orden descendente por fecha de creación
                ->get();

            return response()->json([
                'total' => $invoices->count(),
                'data'  => PaymentsInvoiceResource::collection($invoices),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'No tienes permiso para ver las facturas.'
            ], 403);
        } catch (Throwable $e) {
            Log::error("Error en InvoiceController@indexfilter: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Error al listar las facturas.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(StoreInvoiceRequest $request, InvoiceService $service)
    {
        try {
            Gate::authorize('create', Invoice::class);
            $data = $request->validated();
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            $invoice = $service->create($data);

            return response()->json([
                'message' => $request->input('id')
                    ? 'Factura actualizada correctamente.'
                    : 'Factura creada correctamente.',
                'data' => $invoice
            ], 201);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para crear la factura.'], 403);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Error al crear la factura.', 'error' => $e->getMessage()], 500);
        }
    }
    public function standby(Request $request, $id)
    {
        try {
            $invoice = Invoice::findOrFail($id);
            Gate::authorize('standby', $invoice);
            $invoice->update([
                'status' => 'daStandby',
                'condicion_oportunidad' => 'cerrada',
                'updated_by' => Auth::id(),
            ]);


            $history = HistoryAprobadorInvoice::where('invoice_id', $invoice->id)
                ->latest()
                ->first();
            if ($history && $history->status_conclusion == null) {
                $history?->update([
                    'status_conclusion'  => 'daStandby',
                    'approval_conclusion_by'      => Auth::id(),
                    'approval_conclusion_at'      => now(),
                    'approval_conclusion_comment' => $request->input('comment', 'Factura puesta en standby'),
                ]);
            } else {

                HistoryAprobadorInvoice::create([
                    'invoice_id'       => $invoice->id,
                    'status_conclusion' => 'daStandby',

                    'approval_conclusion_by'      => Auth::id(),
                    'approval_conclusion_at'      => now(),
                    'approval_conclusion_comment' => $request->input('comment', 'Factura puesta en standby'),

                ]);
            }


            return response()->json([
                'message' => 'Factura puesta en standby correctamente.',
                'data' => $invoice
            ], 200);
        } catch (AuthorizationException $e) {
            Log::error('Error de autorización en standby: ' . $e->getMessage());
            return response()->json(['message' => 'No tienes permiso para actualizar esta factura.'], 403);
        } catch (Throwable $e) {
            Log::error('Error al actualizar la factura: ' . $e->getMessage());
            return response()->json(['message' => 'Error al actualizar la factura.', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $invoice = Invoice::with('investments')
                ->findOrFail($id);
            Gate::authorize('view', $invoice);
            return new InvoiceResource($invoice);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Factura no encontrada.'], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para ver esta factura.'], 403);
        } catch (Throwable $e) {
            Log::error('Error al mostrar la factura: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Error al mostrar la factura.'], 500);
        }
    }
    public function activacion(Request $request, $id)
    {
        try {
            $invoice = Invoice::findOrFail($id);
            $userId = Auth::id();
            Gate::authorize('approveLevel1', $invoice);

            // --- VALIDACIÓN NIVEL 1 ---

            //Gate::authorize('aprobar factura nivel 1');

            $invoice->update([
                'approval1_status'  => 'approved',
                'approval1_by'      => $userId,
                'approval1_at'      => now(),
                'approval1_comment' => $request->input('comment'),
                'updated_by'        => $userId,

            ]);

            if ($invoice->approval2_status === 'observed') {
                $invoice->approval2_status = 'pending';
                $invoice->save();
            }



            HistoryAprobadorInvoice::create([
                'invoice_id'       => $invoice->id,
                'approval1_status' => 'approved',
                'approval1_by'     => $userId,
                'approval1_at'     => now(),
                'approval1_comment' => $request->input('comment'),
            ]);

            return response()->json([
                'message' => 'Factura aprobada correctamente en nivel 1.',
                'data'    => $invoice
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso.'], 403);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al aprobar la factura.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    public function observacion(Request $request, $id)
    {
        try {
            $invoice = Invoice::findOrFail($id);
            Gate::authorize('approveLevel1', $invoice);
            $userId = Auth::id();

            // --- Validar comentario requerido ---
            $request->validate([
                'comment' => 'required|string|min:3'
            ], [
                'comment.required' => 'El comentario es obligatorio.',
                'comment.string'   => 'El comentario debe ser texto.',
                'comment.min'      => 'El comentario debe tener al menos 3 caracteres.'
            ]);



            $invoice->update([
                'approval1_status'  => 'observed',
                'approval1_by'      => $userId,
                'approval1_at'      => now(),
                'approval1_comment' => $request->input('comment'),
                'status'            => 'observed',
                'updated_by'        => $userId,
            ]);

            HistoryAprobadorInvoice::create([
                'invoice_id'       => $invoice->id,
                'approval1_status' => 'observed',
                'approval1_by'     => $userId,
                'approval1_at'     => now(),
                'approval1_comment' => $request->input('comment'),
            ]);

            return response()->json([
                'message' => 'Factura observada correctamente.',
                'data'    => $invoice
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso.'], 403);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al observar la factura.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    // public function rechazar(Request $request, $id)
    // {
    //     try {
    //         $invoice = Invoice::findOrFail($id);
    //         Gate::authorize('update', $invoice);

    //         $userId = Auth::id();

    //         // Primera aprobación

    //         $invoice->update([
    //             'approval1_status'  => 'rejected',
    //             'approval1_by'      => $userId,
    //             'approval1_at'      => now(),
    //             'approval1_comment' => $request->input('comment'),
    //             'updated_by'        => $userId,
    //         ]);


    //         HistoryAprobadorInvoice::create([
    //             'invoice_id'       => $invoice->id,
    //             'approval1_status' => 'rejected',
    //             'approval1_by'     => $userId,
    //             'approval1_at'     => now(),
    //             'approval1_comment' => $request->input('comment'),
    //         ]);

    //         return response()->json([
    //             'message' => "Primera aprobación rechazada.",
    //             'data'    => $invoice
    //         ], 200);
    //     } catch (AuthorizationException $e) {
    //         return response()->json(['message' => 'No tienes permiso.'], 403);
    //     } catch (Throwable $e) {
    //         return response()->json([
    //             'message' => 'Error al rechazar la factura.',
    //             'error'   => $e->getMessage()
    //         ], 500);
    //     }
    // }



    public function activacion2(Request $request, $id)
    {
        try {
            $invoice = Invoice::findOrFail($id);
            $userId = Auth::id();
            Gate::authorize('approveLevel2', $invoice);

            //Gate::authorize('aprobar factura nivel 2');

            $invoice->update([
                'approval2_status'  => 'approved',
                'approval2_by'      => $userId,
                'approval2_at'      => now(),
                'approval2_comment' => $request->input('comment'),
                'status'            => 'active',
                'type'              => 'normal',
                'updated_by'        => $userId,
            ]);

            $invoice->condicion_oportunidad = 'abierta';
            $invoice->save();

            $history = HistoryAprobadorInvoice::where('invoice_id', $invoice->id)
                ->latest()
                ->first();


            if ($history && $history->approval2_status == null) {

                $history?->update([
                    'approval2_status'  => 'approved',
                    'approval2_by'      => $userId,
                    'approval2_at'      => now(),
                    'approval2_comment' => $request->input('comment'),
                ]);
            } else {
                HistoryAprobadorInvoice::create([
                    'invoice_id'       => $invoice->id,
                    'approval2_status' => 'approved',
                    'approval2_by'     => $userId,
                    'approval2_at'     => now(),
                    'approval2_comment' => $request->input('comment'),
                ]);
            }

            return response()->json([
                'message' => 'Factura aprobada correctamente en nivel 2.',
                'data'    => $invoice
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso.'], 403);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al aprobar la factura.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    public function observacion2(Request $request, $id)
    {
        try {
            $invoice = Invoice::findOrFail($id);
            Gate::authorize('approveLevel2', $invoice);
            $userId = Auth::id();

            // --- Validar comentario requerido ---
            $request->validate([
                'comment' => 'required|string|min:3'
            ], [
                'comment.required' => 'El comentario es obligatorio.',
                'comment.string'   => 'El comentario debe ser texto.',
                'comment.min'      => 'El comentario debe tener al menos 3 caracteres.'
            ]);







            $invoice->update([
                'approval2_status'  => 'observed',
                'approval1_status'  => 'pending',
                'approval1_by'      => null,
                'approval1_at'      => null,
                'approval1_comment' => null,
                'approval2_by'      => $userId,
                'approval2_at'      => now(),
                'approval2_comment' => $request->input('comment'),
                'status'            => 'observed',
                'updated_by'        => $userId,
            ]);




            $history = HistoryAprobadorInvoice::where('invoice_id', $invoice->id)
                ->latest()
                ->first();



            if ($history && $history->approval2_status == null) {

                $history?->update([
                    'approval2_status'  => 'observed',
                    'approval2_by'      => $userId,
                    'approval2_at'      => now(),
                    'approval2_comment' => $request->input('comment'),
                ]);
            } else {
                HistoryAprobadorInvoice::create([
                    'invoice_id'       => $invoice->id,
                    'approval2_status' => 'observed',
                    'approval2_by'     => $userId,
                    'approval2_at'     => now(),
                    'approval2_comment' => $request->input('comment'),
                ]);
            }

            return response()->json([
                'message' => 'Factura observada correctamente.',
                'data'    => $invoice
            ], 200);
        } catch (AuthorizationException $e) {
            Log::error('Error de autorización en observacion2: ' . $e->getMessage());
            return response()->json(['message' => 'No tienes permiso.'], 403);
        } catch (Throwable $e) {
            Log::error('Error al observar la factura en observacion2: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al observar la factura.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    // public function rechazar2(Request $request, $id)
    // {
    //     try {
    //         $invoice = Invoice::findOrFail($id);
    //         Gate::authorize('update', $invoice);

    //         $userId = Auth::id();




    //         if ($invoice->approval1_status !== 'approved') {
    //             return response()->json([
    //                 'message' => 'No puedes rechazar en la segunda activación hasta que la primera esté resuelta.'
    //             ], 400);
    //         }

    //         $invoice->update([
    //             'approval2_status'  => 'rejected',
    //             'approval2_by'      => $userId,
    //             'approval2_at'      => now(),
    //             'approval2_comment' => $request->input('comment'),
    //             'status'            => 'inactive',
    //             'updated_by'        => $userId,
    //         ]);
    //         $history = HistoryAprobadorInvoice::where('invoice_id', $invoice->id)
    //             ->latest()
    //             ->first();

    //         $history?->update([
    //             'approval2_status'  => 'rejected',
    //             'approval2_by'      => $userId,
    //             'approval2_at'      => now(),
    //             'approval2_comment' => $request->input('comment'),
    //         ]);

    //         return response()->json([
    //             'message' => "Segunda aprobación rechazada.",
    //             'data'    => $invoice
    //         ], 200);
    //     } catch (AuthorizationException $e) {
    //         return response()->json(['message' => 'No tienes permiso.'], 403);
    //     } catch (Throwable $e) {
    //         return response()->json([
    //             'message' => 'Error al rechazar la factura.',
    //             'error'   => $e->getMessage()
    //         ], 500);
    //     }
    // }

    //SOLO PRIMER VALIDADOR

    //FALTA SEGUNDO APROBADOR

    // public function delete($id)
    // {
    //     try {
    //         $invoice = Invoice::findOrFail($id);
    //         Gate::authorize('delete', $invoice);
    //         $invoice->deleted_by = Auth::id();
    //         $invoice->save();
    //         $invoice->delete();
    //         return response()->json(['message' => 'Factura eliminada correctamente.']);
    //     } catch (ModelNotFoundException $e) {
    //         return response()->json(['message' => 'Factura no encontrada.'], 404);
    //     } catch (AuthorizationException $e) {
    //         return response()->json(['message' => 'No tienes permiso para eliminar esta factura.'], 403);
    //     } catch (Throwable $e) {
    //         return response()->json([
    //             'message' => 'Error al eliminar la factura.',
    //             'error'   => $e->getMessage(),
    //             'trace'   => $e->getTraceAsString(),
    //         ], 500);
    //     }
    // }
    public function update(UpdateInvoiceRequest $request, InvoiceService $service, $id)
    {
        try {
            $invoice = Invoice::findOrFail($id);
            Gate::authorize('update', $invoice);
            $data = $request->validated();
            $data['updated_by'] = Auth::id();
            $invoice = $service->update($data, $id);
            Log::info($data);



            $history = HistoryAprobadorInvoice::where('invoice_id', $invoice->id)
                ->latest()
                ->first();
            if ($history && $history->fecha_actualizacion == null) {
                $history?->update([
                    'updated_by'      => Auth::id(),
                    'fecha_actualizacion'      => now(),
                ]);
            } else {

                HistoryAprobadorInvoice::create([
                    'invoice_id'       => $invoice->id,

                    'updated_by'      => Auth::id(),
                    'fecha_actualizacion'      => now(),

                ]);
            }
            return response()->json([
                'message' => 'Factura actualizada correctamente.',
                'data'    => $invoice
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Factura no encontrada.'], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para actualizar la factura.'], 403);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al actualizar la factura.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    public function exportExcel(Request $request)
    {
        try {
            Gate::authorize('export', Invoice::class);
            $search    = $request->input('search', '');
            $status    = $request->input('status');
            $currency  = $request->input('currency');
            $company   = $request->input('company');
            $minAmount = $request->input('min_amount');
            $maxAmount = $request->input('max_amount');
            $minRate   = $request->input('min_rate');
            $maxRate   = $request->input('max_rate');
            $query = app(Pipeline::class)
                ->send(Invoice::query()->with(['company']))
                ->through([
                    new SearchInvoiceFilter($search),
                    new StatusFilter($status),
                    new CurrencyFilter($currency),
                    new CompanyFilter($company),
                    new AmountRangeFilter($minAmount, $maxAmount),
                    new RateRangeFilter($minRate, $maxRate),
                ])
                ->thenReturn();
            $invoices = $query->get();
            return Excel::download(new InvoicesExport($invoices), 'invoices.xlsx');
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'No tienes permiso para exportar facturas.'
            ], 403);
        } catch (Throwable $e) {
            Log::error('Error al exportar facturas: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Error al exportar las facturas.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function approvalHistory($id)
    {

        Gate::authorize('viewAny', Invoice::class);
        $rows = HistoryAprobadorInvoice::query()
            ->where('invoice_id', $id)
            ->with([
                'approval1By:id,name',
                'approval2By:id,name',
                'approvalConclusionBy:id,name',
                'updatedBy:id,name',

            ])
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    // public function adelantarPago(Request $request, Invoice $invoice)
    // {
    //     try {
    //         $data = $request->validate([
    //             'date' => ['required', 'date_format:Y-m-d'],
    //         ]);

    //         // Target pay date as Carbon
    //         $target = Carbon::createFromFormat('Y-m-d', $data['date'])->startOfDay();

    //         // Update invoice dates (use casts on the model)
    //         $invoice->estimated_pay_date = $target;               // cast to date in model
    //         $invoice->due_date           = $target->copy()->subDays(25);

    //         foreach ($invoice->investments as $investment) {
    //             $created = $investment->created_at->copy()->startOfDay();

    //             // Days between creation and new estimated pay date
    //             $days = $created->diffInDays($target, false);     // negative if target < created

    //             // Define your rule; here we clamp to 0 to avoid negative compounding
    //             $days = max(0, $days);

    //             // Update investment due_date to the new target
    //             $investment->due_date = $target;

    //             // Periods in 30-day months
    //             $periods = $days / 30;

    //             // Compound return
    //             $rate = $investment->rate / 100;                  // e.g., 2.5 => 0.025
    //             $newReturn = (pow(1 + $rate, $periods) - 1) * $investment->amount;

    //             $investment->return = MoneyConverter::fromDecimal($newReturn);
    //             $investment->save();
    //         }

    //         $invoice->save();

    //         $history = HistoryAprobadorInvoice::where('invoice_id', $invoice->id)
    //             ->latest()
    //             ->first();

    //         if ($history && $history->status_conclusion == null) {
    //             $history?->update([
    //                 'status_conclusion'  => 'adelantado',
    //                 'approval_conclusion_by'      => Auth::id(),
    //                 'approval_conclusion_at'      => now(),
    //                 'approval_conclusion_comment' => 'Pago adelantado registrado',
    //             ]);
    //         } else {

    //             HistoryAprobadorInvoice::create([
    //                 'invoice_id'       => $invoice->id,
    //                 'status_conclusion' => 'adelantado',

    //                 'approval_conclusion_by'      => Auth::id(),
    //                 'approval_conclusion_at'      => now(),
    //                 'approval_conclusion_comment' => 'Pago adelantado registrado',
    //             ]);
    //         }






    //         return response()->json([
    //             'message' => 'Pago adelantado registrado correctamente.',
    //             'data'    => $invoice->fresh('investments'),
    //         ], 200);
    //     } catch (AuthorizationException $e) {

    //         Log::error('No tienes permiso para crear la factura.', [
    //             'trace' => $e->getTraceAsString(),
    //         ]);
    //         return response()->json(['message' => 'No tienes permiso para crear la factura.'], 403);
    //     } catch (Throwable $e) {
    //         Log::error('Error al crear la factura: ' . $e->getMessage(), [
    //             'trace' => $e->getTraceAsString(),
    //         ]);
    //         return response()->json(['message' => 'Error al crear la factura.', 'error' => $e->getMessage()], 500);
    //     }
    // }

    public function cerrar(Request $request, Invoice $invoice)

    {

        try{
        //Gate::authorize('close', $invoice);

        $request->validate([
            'comentario' => 'nullable|string|max:500',
        ]);
        // (optional) auth/permissions
        // $this->authorize('close', $invoice);
        $history = HistoryAprobadorInvoice::where('invoice_id', $invoice->id)
            ->latest()
            ->first();

        if ($history && $history->status_conclusion == null) {
            $history?->update([
                'status_conclusion'  => 'cerrada',
                'approval_conclusion_by'      => Auth::id(),
                'approval_conclusion_at'      => now(),
                'approval_conclusion_comment' => $request->comentario
            ]);
        } else {

            HistoryAprobadorInvoice::create([
                'invoice_id'       => $invoice->id,
                'status_conclusion' => 'cerrada',

                'approval_conclusion_by'      => Auth::id(),
                'approval_conclusion_at'      => now(),
                'approval_conclusion_comment' => $request->comentario
            ]);
        }


        // do your domain logic here
        // e.g. $invoice->close($request->input('comment'));
        $invoice->condicion_oportunidad = 'cerrada'; // or whatever “closed” means for you
        $invoice->save();

        return response()->json([
            'message' => 'La factura se cerró correctamente',
            'data' => $invoice,
        ]);
    } catch (AuthorizationException $e) {
        return response()->json(['message' => 'No tienes permiso para cerrar la factura.'], 403);
    } catch (Throwable $e) {
        return response()->json([
            'message' => 'Error al cerrar la factura.',
            'error'   => $e->getMessage()
        ], 500);
    }}


    public function abrir(Request $request, Invoice $invoice)
    {

        Gate::authorize('open', $invoice);




        $request->validate([
            'comentario' => 'nullable|string|max:500',
        ]);


        if ($invoice->financed_amount <= 0) {
            return response()->json([
                'message' => 'No se puede abrir la factura. Ya tiene monto financiado.'
            ], 400);
        }

        $today = Carbon::today();
        $limitDate = $today->subDays(25);
        if ($invoice->estimated_pay_date < $limitDate) {
            return response()->json([
                'message' => 'No se puede abrir la factura. La fecha estimada de pago es menor a 25 días desde hoy.'
            ], 400);
        }



        if ($invoice->approval1_status !== 'approved' || $invoice->approval2_status !== 'approved') {
            return response()->json([
                'message' => 'No se puede abrir la factura. Ambos niveles de aprobación deben estar en "approved".'
            ], 400);
        }
        // (optional) auth/permissions
        // $this->authorize('close', $invoice);
        $history = HistoryAprobadorInvoice::where('invoice_id', $invoice->id)
            ->latest()
            ->first();

        if ($history && $history->status_conclusion == null) {
            $history?->update([
                'status_conclusion'  => 'abierta',
                'approval_conclusion_by'      => Auth::id(),
                'approval_conclusion_at'      => now(),
                'approval_conclusion_comment' => $request->comentario
            ]);
        } else {

            HistoryAprobadorInvoice::create([
                'invoice_id'       => $invoice->id,
                'status_conclusion' => 'abierta',

                'approval_conclusion_by'      => Auth::id(),
                'approval_conclusion_at'      => now(),
                'approval_conclusion_comment' => $request->comentario
            ]);
        }


        // do your domain logic here
        // e.g. $invoice->close($request->input('comment'));
        $invoice->condicion_oportunidad = 'abierta'; // or whatever “closed” means for you
        $invoice->save();

        return response()->json([
            'message' => 'La factura se abrió correctamente',
            'data' => $invoice,
        ]);
    }
}
