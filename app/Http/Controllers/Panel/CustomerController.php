<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Investor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller{
    public function listCustomersActivos(Request $request): JsonResponse{
        try {
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search');

            $query = Investor::query()
                ->whereIn('type', ['cliente', 'mixto']) // ← aquí está el cambio
                ->when($search, function ($query) use ($search) {
                    return $query->where(function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('first_last_name', 'LIKE', "%{$search}%")
                        ->orWhere('second_last_name', 'LIKE', "%{$search}%")
                        ->orWhere('document', 'LIKE', "%{$search}%");
                    });
                });

            $investors = $query->paginate($perPage);

            return response()->json([
                'data' => $investors->map(function ($investor) {
                    return [
                        'id' => $investor->id,
                        'documento' => $investor->document,
                        'nombre_completo' => $investor->name . ' ' . $investor->first_last_name . ' ' . $investor->second_last_name,
                        'email' => $investor->email,
                        'type' => $investor->type,
                        'asignado' => $investor->asignado,
                    ];
                }),
                'pagination' => [
                    'total' => $investors->total(),
                    'current_page' => $investors->currentPage(),
                    'per_page' => $investors->perPage(),
                    'last_page' => $investors->lastPage(),
                    'from' => $investors->firstItem(),
                    'to' => $investors->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al listar inversores disponibles',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
