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
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Log;
use Throwable;

class InvoiceController extends Controller{
    private int $codigoCorrelativo = 0;
    public function index(Request $request){
        try {
            Gate::authorize('viewAny', Invoice::class);
            $perPage   = $request->input('per_page', 15);
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
            $invoices = $query->paginate($perPage);
            return InvoiceResource::collection($invoices)
                ->additional([
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
    public function indexfilter(){
        try {
            Gate::authorize('viewAny', Invoice::class);
            $allowedStatus = ['active', 'expired', 'judicialized', 'reprogramed', 'daStandby'];
            $invoices = Invoice::whereIn('status', $allowedStatus)->get();
            return response()->json([
                'total' => $invoices->count(),
                'data'  => InvoiceResource::collection($invoices),
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
    public function store(StoreInvoiceRequest $request, InvoiceService $service){
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
    public function standby(Request $request, $id){
        try {
            Gate::authorize('update', Invoice::class);
            $invoice = Invoice::findOrFail($id);
            $invoice->update([
                'status' => 'daStandby',
                'updated_by' => Auth::id(),
            ]);
            return response()->json([
                'message' => 'Factura puesta en standby correctamente.',
                'data' => $invoice
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para actualizar esta factura.'], 403);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Error al actualizar la factura.', 'error' => $e->getMessage()], 500);
        }
    }
    public function show($id){
        try {
            $invoice = Invoice::findOrFail($id);
            Gate::authorize('view', $invoice);
            if ($invoice->status === 'daStandby') {
                $invoice->load('investments');
            }
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
    public function activacion(Request $request, $id){
        try {
            $invoice = Invoice::findOrFail($id);
            Gate::authorize('update', $invoice);
            $invoice->update([
                'status' => 'active',
                'updated_by' => Auth::id(),
            ]);
            return response()->json([
                'message' => 'Factura activada correctamente.',
                'data' => $invoice
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para actualizar esta factura.'], 403);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al actualizar la factura.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function delete($id){
        try {
            $invoice = Invoice::findOrFail($id);
            Gate::authorize('delete', $invoice);
            $invoice->deleted_by = Auth::id();
            $invoice->save();
            $invoice->delete();
            return response()->json(['message' => 'Factura eliminada correctamente.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Factura no encontrada.'], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para eliminar esta factura.'], 403);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al eliminar la factura.',
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ], 500);
        }
    }
    public function update(UpdateInvoiceRequest $request, InvoiceService $service, $id){
        try {
            $invoice = Invoice::findOrFail($id);
            Gate::authorize('update', $invoice);
            $data = $request->validated();
            $data['updated_by'] = Auth::id();
            $invoice = $service->update($data, $id);
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
    public function exportExcel(Request $request){
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
}
