<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InvoiceController extends Controller{
    public function index(Request $request){
        try {
            $perPage = (int) $request->per_page ?: 10;
            $page = (int) $request->page ?: 1;
            $orderDirection = strtolower($request->orderDirection) === 'asc' ? 'asc' : 'desc';
            $monedas = $request->moneda;
            $plazos = $request->plazo_pago;
            $riesgos = $request->nivel_riesgo;
            $sectores = $request->tipo_industria;
            $ultimas = (bool) $request->ultimas;
            
            $query = Invoice::select(
                'invoices.*',
                'companies.name as company_name',
                'companies.risk as company_risk',
                'companies.sector_id as company_sector_id'
            )
            ->join('companies', 'invoices.company_id', '=', 'companies.id')
            ->whereIn('invoices.status', ['active', 'daStandby'])
            ->where('invoices.due_date', '>=', Carbon::now())
            ->where('invoices.financed_amount', '>', 0); // 🔥 Excluir facturas sin saldo disponible

            if (!empty($monedas) && is_array($monedas)) {
                $query->whereIn('invoices.currency', $monedas);
            }

            if (!empty($plazos) && is_array($plazos)) {
                $query->where(function($q) use ($plazos) {
                    foreach ($plazos as $plazo) {
                        $q->orWhereRaw('DATEDIFF(invoices.estimated_pay_date, invoices.created_at) = ?', [$plazo]);
                    }
                });
            }

            if (!empty($riesgos) && is_array($riesgos)) {
                $map = [
                    'A' => 0,
                    'B' => 1,
                    'C' => 2,
                    'D' => 3,
                    'E' => 4,
                ];
                $riesgos = array_map(fn ($valor) => $map[$valor] ?? $valor, $riesgos);
                $query->whereIn('companies.risk', $riesgos);
            }

            if (!empty($sectores) && is_array($sectores)) {
                $query->whereIn('companies.sector_id', $sectores);
            }

            if ($ultimas) {
                $invoices = $query->orderBy('invoices.created_at', 'desc')
                    ->limit(3)
                    ->get();
                
                $paginatedResponse = [
                    'data' => $invoices,
                    'current_page' => 1,
                    'first_page_url' => null,
                    'from' => 1,
                    'last_page' => 1,
                    'last_page_url' => null,
                    'links' => [],
                    'next_page_url' => null,
                    'path' => request()->url(),
                    'per_page' => $invoices->count(),
                    'prev_page_url' => null,
                    'to' => $invoices->count(),
                    'total' => $invoices->count()
                ];

                return response()->json([
                    'success' => true,
                    'data' => $paginatedResponse,
                ]);
            }

            $invoices = $query->orderBy('companies.name', $orderDirection)
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $invoices,
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => "Error al obtener las facturas",
                'error' => $th->getMessage()
            ], 500);
        }
    }
    public function getSectors(Request $request){
        try {
            $monedas = $request->moneda;
            $plazos = $request->plazo_pago;
            $riesgos = $request->nivel_riesgo;
            $sectores = $request->tipo_industria;
            $sectorId = $request->sector_id;

            $query = Invoice::join('companies', 'invoices.company_id', '=', 'companies.id')
                ->join('sectors', 'companies.sector_id', '=', 'sectors.id')
                ->where('invoices.status', 'active')
                ->where('invoices.due_date', '>=', Carbon::now())
                ->where('invoices.financed_amount', '>', 0);
            if (!empty($monedas) && is_array($monedas)) {
                $query->whereIn('invoices.currency', $monedas);
            }
            if (!empty($plazos) && is_array($plazos)) {
                $query->where(function($q) use ($plazos) {
                    foreach ($plazos as $plazo) {
                        $q->orWhereRaw('DATEDIFF(invoices.estimated_pay_date, invoices.created_at) = ?', [$plazo]);
                    }
                });
            }
            if (!empty($riesgos) && is_array($riesgos)) {
                $map = [
                    'A' => 0,
                    'B' => 1,
                    'C' => 2,
                    'D' => 3,
                    'E' => 4,
                ];
                $riesgos = array_map(function ($valor) use ($map) {
                    return $map[$valor] ?? $valor;
                }, $riesgos);
                $query->whereIn('companies.risk', $riesgos);
            }

            if (!empty($sectores) && is_array($sectores)) {
                $query->whereIn('companies.sector_id', $sectores);
            }

            if (!empty($sectorId)) {
                $query->where('companies.sector_id', $sectorId);
            }

            $sectors = $query
                ->groupBy('sectors.id', 'sectors.name')
                ->orderBy('sectors.name', 'asc')
                ->get(['sectors.id', 'sectors.name']);

            return response()->json([
                'success' => true,
                'data' => $sectors,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => "Error al obtener los sectores",
            ], 500);
        }
    }
    public function show($code){
        try {
            $invoice = Invoice::select('invoices.*', 'companies.name as company_name', 'companies.risk as company_risk')
                ->join('companies', 'invoices.company_id', '=', 'companies.id')
                ->where('invoice_code', $code)
                ->where('invoices.status', 'active')
                ->first();

            return response()->json([
                'success' => true,
                'data' => $invoice,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    public function paid(Request $request){
        try {
            $quantity = (int)$request->quantity ? (int)$request->quantity : -1;
            $invoices = Invoice::select(
                'id',
                'company_id',
                'invoice_code',
                'amount',
                'currency',
                'financed_amount',
                'rate',
                'due_date',
                'created_at',
                'updated_at',
                'estimated_pay_date',
            )
                ->where('status', 'paid')
                ->limit($quantity)
                ->with(['company:id,name,risk'])
                ->orderBy('updated_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $invoices,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
