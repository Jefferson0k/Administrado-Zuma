<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Investment;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Validation\Rule;

class InvestorDashboardController extends Controller {

    public function investment(Request $request) {
        try {
            $validatedData = $request->validate([
                'currency' => [
                    'required',
                    'string',
                    Rule::in(['PEN', 'USD']),
                ],
            ]);

            $currency = $validatedData['currency'];
            $token = PersonalAccessToken::findToken(request()->bearerToken());
            $investor = $token->tokenable;
            $investorId = $investor->id;

            $results = Investment::query()
                ->selectRaw("
                    DATE_FORMAT(investments.created_at, '%b') AS mes,
                    SUM(investments.amount) AS total_mes,
                    SUM(
                        CASE 
                            WHEN invoices.statusPago = 'paid' 
                            THEN investments.return_efectivizado 
                            ELSE 0 
                        END
                    ) AS retorno_cobrado
                ")
                ->join('investors', 'investments.investor_id', '=', 'investors.id')
                ->leftJoin('invoices', 'investments.invoice_id', '=', 'invoices.id')
                ->where('investors.id', $investorId)
                ->where('investments.currency', '=', $currency)
                ->groupBy(DB::raw("DATE_FORMAT(investments.created_at, '%b')"))
                ->get();
            return response()->json([
                        'success' => true,
                        'data' => $results,
            ]);
        } catch (Throwable $th) {
            return response()->json([
                        'success' => false,
                        'message' => $th->getMessage(),
                            ], in_array((int) $th->getCode(), range(100, 599)) ? (int) $th->getCode() : 500);
        }
    }
    
    public function investmentByEmpresa(Request $request) {
        try {
            $token = PersonalAccessToken::findToken(request()->bearerToken());
            $investor = $token->tokenable;
            $investorId = $investor->id;
            $validatedData = $request->validate([
                'currency' => [
                    'required',
                    'string',
                    Rule::in(['PEN', 'USD']),
                ],
            ]);
            $currency = $validatedData['currency'];
            
            
            $inversiones = Investment::query()
                ->select(
                    'companies.name',
                    DB::raw('SUM(investments.amount) as total_mes')
                )
                ->join('investors', 'investments.investor_id', '=', 'investors.id')
                ->join('invoices', 'investments.invoice_id', '=', 'invoices.id')
                ->join('companies', 'invoices.company_id', '=', 'companies.id')
                ->where('investors.id', $investorId)
                ->where('investments.currency', '=', $currency)
                ->groupBy('companies.name')
                ->get();

            // Calcular el total global
            $total = $inversiones->sum('total_mes');

            // Calcular el 10% del total
            $threshold = $total * 0.10;

            // Separar las empresas grandes y pequeñas
            $grandes = $inversiones->filter(fn($inv) => $inv->total_mes >= $threshold);
            $pequenas = $inversiones->filter(fn($inv) => $inv->total_mes < $threshold);

            // ✅ Agregar las pequeñas como "Otras empresas" con detalle
            if ($pequenas->isNotEmpty()) {
                $otras = (object)[
                    'name' => 'Otras empresas',
                    'total_mes' => $pequenas->sum('total_mes'),
                    'detalle_otras_empresas' => $pequenas->map(fn($p) => "{$p->name} ({$p->total_mes})")->implode(', '), // 👈 texto concatenado
                    'detalle_otras_empresas_array' => $pequenas->map(fn($p) => [
                        'name' => $p->name,
                        'total_mes' => $p->total_mes,
                    ])->values(), // 👈 array con nombre y monto
                ];

                $resultado = $grandes->values()->push($otras);
            } else {
                $resultado = $grandes->values();
            }

            
            return response()->json([
                        'success' => true,
                        'data' => $resultado,
            ]);
            
            
        } catch (Throwable $th) {
            return response()->json([
                        'success' => false,
                        'message' => $th->getMessage(),
                            ], in_array((int) $th->getCode(), range(100, 599)) ? (int) $th->getCode() : 500);
        }
    }
    
    public function investmentBySector(Request $request) {
        try {
            $token = PersonalAccessToken::findToken(request()->bearerToken());
            $investor = $token->tokenable;
            $investorId = $investor->id;
            $validatedData = $request->validate([
                'currency' => [
                    'required',
                    'string',
                    Rule::in(['PEN', 'USD']),
                ],
            ]);
            $currency = $validatedData['currency'];
            
            $inversiones = Investment::query()
            ->select(
                'sectors.name',
                DB::raw('SUM(investments.amount) as total_mes')
            )
            ->join('investors', 'investments.investor_id', '=', 'investors.id')
            ->join('invoices', 'investments.invoice_id', '=', 'invoices.id')
            ->join('companies', 'invoices.company_id', '=', 'companies.id')
            ->join('sectors', 'companies.sector_id', '=', 'sectors.id')
            ->where('investors.id', $investorId)
            ->where('investments.currency', '=', $currency)
            ->groupBy('sectors.name')
            ->get();
            
            return response()->json([
                        'success' => true,
                        'data' => $inversiones,
            ]);
        } catch (Throwable $th) {
            return response()->json([
                        'success' => false,
                        'message' => $th->getMessage(),
                            ], in_array((int) $th->getCode(), range(100, 599)) ? (int) $th->getCode() : 500);
        }
    }
    
    public function investmentByRiesgo(Request $request) {
        try {
            $token = PersonalAccessToken::findToken(request()->bearerToken());
            $investor = $token->tokenable;
            $investorId = $investor->id;
            $validatedData = $request->validate([
                'currency' => [
                    'required',
                    'string',
                    Rule::in(['PEN', 'USD']),
                ],
            ]);
            $currency = $validatedData['currency'];
            $inversiones = Investment::query()
                ->select(
                    'companies.risk as name',
                    DB::raw('SUM(investments.amount) as total_mes')
                )
                ->join('investors', 'investments.investor_id', '=', 'investors.id')
                ->join('invoices', 'investments.invoice_id', '=', 'invoices.id')
                ->join('companies', 'invoices.company_id', '=', 'companies.id')
                ->where('investors.id', $investorId)
                ->where('investments.currency', '=', $currency)
                ->groupBy('companies.risk')
                ->get();
            return response()->json([
                        'success' => true,
                        'data' => $inversiones,
            ]);
        } catch (Throwable $th) {
            return response()->json([
                        'success' => false,
                        'message' => $th->getMessage(),
                            ], in_array((int) $th->getCode(), range(100, 599)) ? (int) $th->getCode() : 500);
        }
    }
    
    public function investmentByEmpresaReturn(Request $request) {
        try {
            $token = PersonalAccessToken::findToken(request()->bearerToken());
            $investor = $token->tokenable;
            $investorId = $investor->id;
            $validatedData = $request->validate([
                'currency' => [
                    'required',
                    'string',
                    Rule::in(['PEN', 'USD']),
                ],
            ]);
            $currency = $validatedData['currency'];
            
            $inversiones = Investment::query()
                ->select(
                    'companies.name',
                    DB::raw('SUM(investments.return_efectivizado) as total_mes')
                )
                ->join('investors', 'investments.investor_id', '=', 'investors.id')
                ->join('invoices', 'investments.invoice_id', '=', 'invoices.id')
                ->join('companies', 'invoices.company_id', '=', 'companies.id')
                ->where('investors.id', $investorId)
                ->where('investments.currency', '=', $currency)
                ->where('invoices.statusPago', 'paid')
                ->groupBy('companies.name')
                ->get();

            // Calcular el total global
            $total = $inversiones->sum('total_mes');

            // Calcular el 10% del total
            $threshold = $total * 0.10;

            // Separar las empresas grandes y pequeñas
            $grandes = $inversiones->filter(fn($inv) => $inv->total_mes >= $threshold);
            $pequenas = $inversiones->filter(fn($inv) => $inv->total_mes < $threshold);

            // ✅ Sumar las pequeñas como "Otras empresas"
            if ($pequenas->isNotEmpty()) {
                $otras = (object)[
                    'name' => 'Otras empresas',
                    'total_mes' => $pequenas->sum('total_mes'),
                ];
                $resultado = $grandes->values()->push($otras);
            } else {
                $resultado = $grandes->values();
            }

            
            return response()->json([
                        'success' => true,
                        'data' => $resultado,
            ]);
        } catch (Throwable $th) {
            return response()->json([
                        'success' => false,
                        'message' => $th->getMessage(),
                            ], in_array((int) $th->getCode(), range(100, 599)) ? (int) $th->getCode() : 500);
        }
    }
    
    public function investmentBySectorReturn(Request $request) {
        try {
            $token = PersonalAccessToken::findToken(request()->bearerToken());
            $investor = $token->tokenable;
            $investorId = $investor->id;
            $validatedData = $request->validate([
                'currency' => [
                    'required',
                    'string',
                    Rule::in(['PEN', 'USD']),
                ],
            ]);
            $currency = $validatedData['currency'];
            
            $inversiones = Investment::query()
            ->select(
                'sectors.name',
                DB::raw('SUM(investments.return_efectivizado) as total_mes')
            )
            ->join('investors', 'investments.investor_id', '=', 'investors.id')
            ->join('invoices', 'investments.invoice_id', '=', 'invoices.id')
            ->join('companies', 'invoices.company_id', '=', 'companies.id')
            ->join('sectors', 'companies.sector_id', '=', 'sectors.id')
            ->where('investors.id', $investorId)
            ->where('investments.currency', '=', $currency)  
            ->where('invoices.statusPago', 'paid')
            ->groupBy('sectors.name')
            ->get();
            
            return response()->json([
                        'success' => true,
                        'data' => $inversiones,
            ]);
        } catch (Throwable $th) {
            return response()->json([
                        'success' => false,
                        'message' => $th->getMessage(),
                            ], in_array((int) $th->getCode(), range(100, 599)) ? (int) $th->getCode() : 500);
        }
    }
    
    public function investmentByRiesgoReturn(Request $request) {
        try {
            $token = PersonalAccessToken::findToken(request()->bearerToken());
            $investor = $token->tokenable;
            $investorId = $investor->id;
            $validatedData = $request->validate([
                'currency' => [
                    'required',
                    'string',
                    Rule::in(['PEN', 'USD']),
                ],
            ]);
            $currency = $validatedData['currency'];
            
            $inversiones = Investment::query()
                ->select(
                    'companies.risk as name',
                    DB::raw('SUM(investments.return_efectivizado) as total_mes')
                )
                ->join('investors', 'investments.investor_id', '=', 'investors.id')
                ->join('invoices', 'investments.invoice_id', '=', 'invoices.id')
                ->join('companies', 'invoices.company_id', '=', 'companies.id')
                ->where('investors.id', $investorId)
                ->where('investments.currency', '=', $currency)  
                ->where('invoices.statusPago', 'paid')
                ->groupBy('companies.risk')
                ->get();
            return response()->json([
                        'success' => true,
                        'data' => $inversiones,
            ]);
        } catch (Throwable $th) {
            return response()->json([
                        'success' => false,
                        'message' => $th->getMessage(),
                            ], in_array((int) $th->getCode(), range(100, 599)) ? (int) $th->getCode() : 500);
        }
    }
    
    public function investmentTotal(Request $request) {
        try {
            $token = PersonalAccessToken::findToken(request()->bearerToken());
            $investor = $token->tokenable;
            $investorId = $investor->id;
            $validatedData = $request->validate([
                'currency' => [
                    'required',
                    'string',
                    Rule::in(['PEN', 'USD']),
                ],
            ]);
            $currency = $validatedData['currency'];
            
            $result = Investment::selectRaw("
            SUM(investments.amount) AS total_invertido,
            SUM(CASE WHEN invoices.statusPago = 'paid' THEN investments.return_efectivizado ELSE 0 END) AS retornos_cobrados,
            SUM(CASE WHEN invoices.statusPago IS NULL THEN investments.return_efectivizado ELSE 0 END) AS pendiente_cobrar,
            (
                (SUM(CASE WHEN invoices.statusPago = 'paid' THEN investments.return_efectivizado ELSE 0 END) /
                 SUM(investments.amount)) * 100
            ) AS rentabilidad_anual_promedio
            ")
            ->join('investors', 'investments.investor_id', '=', 'investors.id')
            ->join('invoices', 'investments.invoice_id', '=', 'invoices.id')
            ->join('companies', 'invoices.company_id', '=', 'companies.id')
            ->where('investors.id', $investorId)
            ->where('investments.currency', '=', $currency) 
            ->first();
            return response()->json([
                        'success' => true,
                        'data' => $result,
            ]);
        } catch (Throwable $th) {
            return response()->json([
                        'success' => false,
                        'message' => $th->getMessage(),
                            ], in_array((int) $th->getCode(), range(100, 599)) ? (int) $th->getCode() : 500);
        }
    }
    
    public function investmentCartera(Request $request) {
        try {
            $token = PersonalAccessToken::findToken(request()->bearerToken());
            $investor = $token->tokenable;
            $investorId = $investor->id;
            $validatedData = $request->validate([
                'currency' => [
                    'required',
                    'string',
                    Rule::in(['PEN', 'USD']),
                ],
            ]);
            $currency = $validatedData['currency'];
            
            $result = DB::table('investments as nvm')
            ->selectRaw("
                COUNT(IF(ivs.statusPago = 'paid', nvm.return_efectivizado, NULL)) AS retornos_cobrados,
                COUNT(IF(ivs.statusPago IS NULL, nvm.return_efectivizado, NULL)) AS pendiente_cobrar,
                (
                    COUNT(IF(ivs.statusPago = 'paid', nvm.return_efectivizado, NULL)) + 
                    COUNT(IF(ivs.statusPago IS NULL, nvm.return_efectivizado, NULL))
                ) AS total,
                ROUND(
                    (COUNT(IF(ivs.statusPago = 'paid', nvm.return_efectivizado, NULL)) /
                    (
                        COUNT(IF(ivs.statusPago = 'paid', nvm.return_efectivizado, NULL)) + 
                        COUNT(IF(ivs.statusPago IS NULL, nvm.return_efectivizado, NULL))
                    )) * 100, 2
                ) AS porcentaje_cobrados,
                ROUND(
                    (COUNT(IF(ivs.statusPago IS NULL, nvm.return_efectivizado, NULL)) /
                    (
                        COUNT(IF(ivs.statusPago = 'paid', nvm.return_efectivizado, NULL)) + 
                        COUNT(IF(ivs.statusPago IS NULL, nvm.return_efectivizado, NULL))
                    )) * 100, 2
                ) AS porcentaje_pendientes
            ")
            ->join('investors as nvs', 'nvm.investor_id', '=', 'nvs.id')
            ->join('invoices as ivs', 'nvm.invoice_id', '=', 'ivs.id')
            ->join('companies as co', 'ivs.company_id', '=', 'co.id')
            ->where('nvs.id', '=', $investorId)
            ->where('nvm.currency', '=', $currency)
            ->first();
            
            
            return response()->json([
                        'success' => true,
                        'data' => $result,
            ]);
        } catch (Throwable $th) {
            return response()->json([
                        'success' => false,
                        'message' => $th->getMessage(),
                            ], in_array((int) $th->getCode(), range(100, 599)) ? (int) $th->getCode() : 500);
        }
    }
    
    
    public function investmentDiversificacion(Request $request) {
        try {
            $token = PersonalAccessToken::findToken(request()->bearerToken());
            $investor = $token->tokenable;
            $investorId = $investor->id;
            $validatedData = $request->validate([
                'currency' => [
                    'required',
                    'string',
                    Rule::in(['PEN', 'USD']),
                ],
            ]);
            $currency = $validatedData['currency'];
            
            $result = DB::table('investments as nvm')
                ->join('investors as nvs', 'nvm.investor_id', '=', 'nvs.id')
                ->join('invoices as ivs', 'nvm.invoice_id', '=', 'ivs.id')
                ->join('companies as co', 'ivs.company_id', '=', 'co.id')
                ->join('sectors as se', 'co.sector_id', '=', 'se.id')
                ->where('nvs.id', $investorId)
                ->where('nvm.currency', '=', $currency)
                ->selectRaw('COUNT(DISTINCT se.id) as sectores')
                ->first();

            // acceder al valor
            $sectores = $result;
            
            
            return response()->json([
                        'success' => true,
                        'data' => $sectores,
            ]);
        } catch (Throwable $th) {
            return response()->json([
                        'success' => false,
                        'message' => $th->getMessage(),
                            ], in_array((int) $th->getCode(), range(100, 599)) ? (int) $th->getCode() : 500);
        }
    }
    
    
}
