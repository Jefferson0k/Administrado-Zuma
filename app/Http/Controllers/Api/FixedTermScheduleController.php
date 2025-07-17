<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Tasas\FixedTermSchedule\FixedTermScheduleResource;
use App\Models\FixedTermSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FixedTermScheduleController extends Controller{
    public function showCronograma(string $id): JsonResponse{
        $schedules = FixedTermSchedule::where('fixed_term_investment_id', $id)
            ->orderBy('month')
            ->get();
        $total = $schedules->count();
        $pendientes = $schedules->where('status', 'pendiente')->count();
        $pagados = $schedules->where('status', 'pagado')->count();
        return response()->json([
            'success' => true,
            'totals' => [
                'total'     => $total,
                'pendiente' => $pendientes,
                'pagado'    => $pagados,
            ],
            'data' => FixedTermScheduleResource::collection($schedules),
        ]);
    }
}
