<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\MoneyConverter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class BalanceController extends Controller{
    public function index(Request $request){
        try {
            /** @var \App\Models\User $investor */
            $investor = Auth::user();

            $balances = $investor->balances()->get();

            return response()->json([
                'success' => true,
                'data' => $balances,
            ]);

            $balances_status = [];

            foreach ($balances as $key => $balance) {

                $invested = $investor->investments()->where('status', 'active')->where('currency', $balance->currency)->sum('amount');
                
                // Debug para ver qué inversiones están siendo consideradas
                $investments_debug = $investor->investments()->where('currency', $balance->currency)->get(['status', 'return', 'amount']);
                Log::info('Inversiones para moneda ' . $balance->currency, $investments_debug->toArray());
                
                $expected = $investor->investments()->whereIn('status', ['active', 'reprogramed'])->where('currency', $balance->currency)->sum('return');
                Log::info('Expected calculado: ' . $expected);

                $balance_info = (object) [
                    "currency" => '',
                    "available" => 0,
                    "invested" => 0,
                    "expected" => 0
                ];

                $balance_info->currency = $balance->currency;
                $balance_info->available = MoneyConverter::getValue($balance->amount);
                $balance_info->invested = $balance->investment_amount;
                $balance_info->expected = $expected;

                array_push($balances_status, $balance_info);
            }

            return response()->json([
                'success' => true,
                'data' => $balances_status,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
