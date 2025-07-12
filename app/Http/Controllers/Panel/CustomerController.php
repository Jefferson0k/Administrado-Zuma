<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller{
    public function listCustomersActivos(Request $request): JsonResponse{
        try {
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search');
            $query = Customer::query()
                ->where('estado', 1)
                ->when($search, function ($query) use ($search) {
                    return $query->where(function ($q) use ($search) {
                        $q->where('nombre', 'LIKE', "%{$search}%")
                        ->orWhere('apellidos', 'LIKE', "%{$search}%")
                        ->orWhere('dni', 'LIKE', "%{$search}%");
                    });
                });
            $customers = $query->paginate($perPage);
            return response()->json([
                'data' => $customers->map(function ($customer) {
                    return [
                        'id' => $customer->id,
                        'dni' => $customer->dni,
                        'nombre' => $customer->nombre,
                        'apellidos' => $customer->apellidos,
                        'estado' => $customer->estado,
                    ];
                }),
                'pagination' => [
                    'total' => $customers->total(),
                    'current_page' => $customers->currentPage(),
                    'per_page' => $customers->perPage(),
                    'last_page' => $customers->lastPage(),
                    'from' => $customers->firstItem(),
                    'to' => $customers->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al listar clientes',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
