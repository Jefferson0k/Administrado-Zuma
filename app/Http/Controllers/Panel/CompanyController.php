<?php

namespace App\Http\Controllers\Panel;

use App\Exports\CompaniesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreCompanyRequest;
use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Http\Requests\CompanyFinance\StoreCompanyFinanceRequest;
use App\Http\Resources\Factoring\Company\CompanyFinanceResource;
use App\Http\Resources\Factoring\Company\CompanyResource;
use App\Models\Company;
use App\Models\CompanyFinance;
use App\Models\Invoice;
use App\Pipelines\RiskFilter;
use App\Pipelines\SearchCompanyFilter;
use App\Pipelines\SectorFilter;
use App\Pipelines\SubsectorFilter;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class CompanyController extends Controller{
     public function index(Request $request){
        try {
            Gate::authorize('viewAny', Company::class);

            // Debug switch (?debug=1)
            $debug = $request->boolean('debug', false);
            if ($debug) {
                DB::enableQueryLog();
            }

            Log::info('Company.index incoming', [
                'query' => $request->query(),
                'path'  => $request->path(),
            ]);


            $perPage     = $request->input('per_page', 15);
            $search      = $request->input('search', '');
            $sectorId    = $request->input('sector_id');
            $subsectorId = $request->input('subsector_id');
            $risk        = $request->input('risk');

            $sortField   = $request->input('sort_field'); // ui fields
            $sortOrder   = strtolower($request->input('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';

            // UI -> real columns
            $sortableMap = [
                'document'      => 'companies.document',
                'business_name' => 'companies.business_name',
                'name'          => 'companies.name',
                'risk'          => 'companies.risk',
                'creacion'      => 'companies.created_at',
                'sectornom'     => 'sectors.name',
                'subsectornom'  => 'subsectors.name',
            ];


            $query = app(Pipeline::class)
                ->send(Company::query()->with(['sector', 'subsector']))
                ->through([
                    new SearchCompanyFilter($search),
                    new SectorFilter($sectorId),
                    new SubsectorFilter($subsectorId),
                    new RiskFilter($risk),
                ])
                ->thenReturn()
                ->latest(); // ORDER BY created_at DESC


            $sortApplied = ['companies.id', 'desc'];

            if ($sortField && isset($sortableMap[$sortField])) {
                $column = $sortableMap[$sortField];
                $sortApplied = [$column, $sortOrder];

                if ($sortField === 'sectornom') {
                    $query->leftJoin('sectors', 'companies.sector_id', '=', 'sectors.id')
                          ->select('companies.*')
                          ->orderBy($column, $sortOrder);
                } elseif ($sortField === 'subsectornom') {
                    $query->leftJoin('subsectors', 'companies.subsector_id', '=', 'subsectors.id')
                          ->select('companies.*')
                          ->orderBy($column, $sortOrder);
                } else {
                    $query->orderBy($column, $sortOrder);
                }
            } else {
                $query->latest('companies.id');
            }

            if ($debug) {
                $sql = (clone $query)->toSql();
                $bindings = (clone $query)->getBindings();
                Log::info('Company.index SQL', ['sql' => $sql, 'bindings' => $bindings]);
            }

            $companies = $query->paginate($perPage);

            if ($debug) {
                Log::info('Company.index QueryLog', DB::getQueryLog());
            }


            return CompanyResource::collection($companies)
                ->additional([
                    'total' => $companies->total(),
                    'meta'  => [
                        'sort_field_requested' => $sortField,
                        'sort_order_requested' => $request->input('sort_order'),
                        'sort_field_applied'   => $sortApplied[0] ?? null,
                        'sort_order_applied'   => $sortApplied[1] ?? null,
                    ],
                ]);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para ver las compañías.'], 403);
            return response()->json(['message' => 'No tienes permiso para ver las compañías.'], 403);
        } catch (Throwable $e) {
            Log::error('Error al listar las compañías', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => collect($e->getTrace())->take(3),
            ]);
            return response()->json(['message' => 'Error al listar las compañías.'], 500);
        }
    }

    public function store(StoreCompanyRequest $request, StoreCompanyFinanceRequest $financeRequest)
    {
        try {
            Gate::authorize('create', Company::class);
            $data = $request->validated();
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            $company = DB::transaction(function () use ($data, $financeRequest) {
                $company = Company::create($data);
                $financeData = $financeRequest->validated();
                $financeData['company_id'] = $company->id;
                $financeData['created_by'] = Auth::id();
                $financeData['updated_by'] = Auth::id();
                CompanyFinance::create($financeData);
                return $company;
            });
            return response()->json([
                'message' => 'Empresa y finanzas creadas correctamente.',
                'data'    => new CompanyResource($company)
            ], 201);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para crear una empresa.'], 403);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al crear la empresa.',
                'error'   => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine()
            ], 500);
        }
    }
    public function show($id)
    {
        try {
            $company = Company::with(['sector', 'subsector', 'finances'])
                ->findOrFail($id);
            Gate::authorize('view', $company);
            return new CompanyResource($company);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Empresa no encontrada.'], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para ver esta empresa.'], 403);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Error al mostrar la empresa.'], 500);
        }
    }
    public function update(UpdateCompanyRequest $request, $id)
    {
        try {
            $company = Company::findOrFail($id);
            Gate::authorize('update', $company);
            $data = $request->validated();
            $data['updated_by'] = Auth::id();
            $company->update($data);
            return response()->json([
                'message' => 'Empresa actualizada correctamente.',
                'data'    => new CompanyResource($company)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Empresa no encontrada.'], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para editar esta empresa.'], 403);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Error al actualizar la empresa.'], 500);
        }
    }
    public function delete($id)
    {
        try {
            $company = Company::findOrFail($id);
            Gate::authorize('delete', $company);
            $company->deleted_by = Auth::id();
            $company->save();
            $company->delete();
            return response()->json(['message' => 'Empresa eliminada correctamente.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Empresa no encontrada.'], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para eliminar esta empresa.'], 403);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Error al eliminar la empresa.'], 500);
        }
    }
    public function historicalData($companyId)
    {
        $company = Company::find($companyId);
        if (!$company) {
            return response()->json([
                "success" => false,
                "message" => "No se encontró la empresa"
            ], 404);
        }
        $existingFinanceData = CompanyFinance::where('company_id', $companyId)->first();

        $invoices = Invoice::where('company_id', $companyId)->get();

        if ($invoices->isEmpty() && !$existingFinanceData) {
            return response()->json([
                "success" => false,
                "message" => "No se encontró información financiera para esta empresa"
            ], 404);
        }
        $currentTotalFacturasFinanciadas = $invoices->count();
        $currentMontoTotalFinanciado = $invoices->sum(function ($invoice) {
            return floatval($invoice->amount);
        });
        $currentFacturasPagadas = $invoices->where('status', 'paid')->count();
        $currentFacturasPendientes = $invoices->whereIn('status', ['active', 'inactive'])->count();
        $facturasReprogramadas = $invoices->where('status', 'reprogramed')->count();
        $facturasInactivas = $invoices->where('status', 'inactive')->count();

        // Calcular plazo promedio de pago actual
        $facturasPagadasCollection = $invoices->where('status', 'paid');
        $currentPlazoPromedioPago = 0;

        if ($facturasPagadasCollection->count() > 0) {
            $totalDias = $facturasPagadasCollection->sum(function ($invoice) {
                $dueDate = new \DateTime($invoice->due_date);
                $estimatedPayDate = new \DateTime($invoice->estimated_pay_date);
                return $dueDate->diff($estimatedPayDate)->days;
            });
            $currentPlazoPromedioPago = round($totalDias / $facturasPagadasCollection->count());
        }

        // Sumar datos históricos con datos actuales
        $totalFacturasFinanciadas = $currentTotalFacturasFinanciadas +
            ($existingFinanceData ? $existingFinanceData->facturas_financiadas : 0);

        $totalMontoFinanciado = $currentMontoTotalFinanciado +
            ($existingFinanceData ? floatval($existingFinanceData->monto_total_financiado) : 0);

        $totalFacturasPagadas = $currentFacturasPagadas +
            ($existingFinanceData ? $existingFinanceData->pagadas : 0);

        $totalFacturasPendientes = $currentFacturasPendientes +
            ($existingFinanceData ? $existingFinanceData->pendientes : 0);

        // Calcular plazo promedio ponderado
        $plazoPromedioPago = 0;
        if ($existingFinanceData && $existingFinanceData->plazo_promedio_pago > 0 && $currentPlazoPromedioPago > 0) {
            // Promedio ponderado entre histórico y actual
            $totalFacturasPagadasHistorico = $existingFinanceData->pagadas;
            $plazoPromedioPago = round(
                (($existingFinanceData->plazo_promedio_pago * $totalFacturasPagadasHistorico) +
                    ($currentPlazoPromedioPago * $currentFacturasPagadas)) /
                    ($totalFacturasPagadasHistorico + $currentFacturasPagadas)
            );
        } elseif ($existingFinanceData && $existingFinanceData->plazo_promedio_pago > 0) {
            $plazoPromedioPago = $existingFinanceData->plazo_promedio_pago;
        } else {
            $plazoPromedioPago = $currentPlazoPromedioPago;
        }

        // Calcular montos pagados y pendientes
        $montoPagado = $invoices->sum(function ($invoice) {
            return floatval($invoice->paid_amount);
        });

        $montoPendiente = $totalMontoFinanciado - $montoPagado;

        $financeData = (object) [
            'id' => $existingFinanceData ? $existingFinanceData->id : null,
            'company_id' => $companyId,
            'company' => $company,
            'facturas_financiadas' => $totalFacturasFinanciadas,
            'monto_total_financiado' => number_format($totalMontoFinanciado, 2, '.', ''),
            'pagadas' => $totalFacturasPagadas,
            'pendientes' => $totalFacturasPendientes,
            'reprogramadas' => $facturasReprogramadas,
            'inactivas' => $facturasInactivas,
            'plazo_promedio_pago' => $plazoPromedioPago,
            'monto_pagado' => number_format($montoPagado, 2, '.', ''),
            'monto_pendiente' => number_format($montoPendiente, 2, '.', '')
        ];

        return new CompanyFinanceResource($financeData);
    }
    public function showcompany(string $id)
    {
        try {
            $company = Company::find($id);
            if (!$company) return response()->json([
                'success' => false,
                'message' => 'No se encontró la empresa',
            ], 404);

            return response()->json([
                'success' => true,
                'data' => $company,
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    public function searchCompany(Request $request)
    {
        try {
            Gate::authorize('viewAny', Company::class);
            $perPage = $request->input('per_page', 10);
            $search  = $request->input('search');
            $query = Company::query()
                ->when($search, function ($query) use ($search) {
                    return $query->where(function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('business_name', 'LIKE', "%{$search}%")
                            ->orWhere('id', 'LIKE', "%{$search}%")
                            ->orWhere('document', 'LIKE', "%{$search}%")
                            ->orWhere('incorporation_year', 'LIKE', "%{$search}%")
                            ->orWhere('description', 'LIKE', "%{$search}%");
                    });
                })
                ->with(['sector', 'subsector', 'finances']);
            $companies = $query->paginate($perPage);
            return response()->json([
                'data' => $companies->map(function ($company) {
                    return [
                        'id'                => $company->id,
                        'name'              => $company->name,
                        'business_name'     => $company->business_name,
                        'document'          => $company->document,
                        'risk'              => $company->risk,
                        'incorporation_year' => $company->incorporation_year,
                    ];
                }),
                'pagination' => [
                    'total'        => $companies->total(),
                    'current_page' => $companies->currentPage(),
                    'per_page'     => $companies->perPage(),
                    'last_page'    => $companies->lastPage(),
                    'from'         => $companies->firstItem(),
                    'to'           => $companies->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Error al buscar empresas',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function exportExcel(Request $request)
    {
        Gate::authorize('export', Company::class);
        $search = $request->input('search', '');
        $sectorId = $request->input('sector_id');
        $subsectorId = $request->input('subsector_id');
        $risk = $request->input('risk');
        $query = app(Pipeline::class)
            ->send(Company::query()->with(['sector', 'subsector']))
            ->through([
                new SearchCompanyFilter($search),
                new SectorFilter($sectorId),
                new SubsectorFilter($subsectorId),
                new RiskFilter($risk),
            ])
            ->thenReturn();
        $companies = $query->get();
        $currentDateTime = Carbon::now()->format('d-m-Y_H-i-s');
        $fileName = "empresas_{$currentDateTime}.xlsx";
        return Excel::download(new CompaniesExport($companies), $fileName);
    }
}
