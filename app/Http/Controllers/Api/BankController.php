<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bank;

class BankController extends Controller{
    public function index(){
        try {
            $banks = Bank::all();

            return response()->json([
                'success' => true,
                'data' => $banks,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
