<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Resources\Factoring\Invoice\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class InvoiceController extends Controller{
    private int $codigoCorrelativo = 0;
    public function index(){
        try {
            Gate::authorize('viewAny', Invoice::class);
            $invoice = Invoice::all();
            return response()->json([
                'total' => $invoice->count(),
                'data'  => InvoiceResource::collection($invoice),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'No tienes permiso para ver las facturas.'
            ], 403);
        } catch (Throwable $e) {
            Log::error("Error en InvoiceController@index: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Error al listar las facturas.',
                'error' => $e->getMessage()
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
            $invoice = $service->save($data, $request->input('id'));
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
            //Gate::authorize('update', Invoice::class);
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
            return new InvoiceResource($invoice);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Sector no encontrada.'], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para ver esta factura.'], 403);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Error al mostrar la factura.'], 500);
        }
    }
    public function activacion(Request $request, $id){
        try {
            //Gate::authorize('update', Invoice::class);
            $invoice = Invoice::findOrFail($id);
            $invoice->update([
                'status' => 'active',
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
}
