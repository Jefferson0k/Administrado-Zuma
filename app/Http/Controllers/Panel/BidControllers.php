<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\Subastas\Bid\BidResource;
use App\Models\Bid;
use Illuminate\Http\Request;

class BidControllers extends Controller{
    public function index(Request $request)
    {
        $bids = Bid::with([
            'subasta' => function ($query) {
                // 🔹 Cambiado de 'property' a 'solicitud'
                $query->with(['ganador', 'solicitud']);
            },
            'investor'
        ])
            ->get()
            ->groupBy('subasta_id')
            ->map(function ($groupedBids) {
                return $groupedBids->sortByDesc('monto')->values()->map(function ($bid, $index) {
                    $bid->puesto = $index + 1;
                    $bid->es_ganador = $index === 0 && $bid->subasta && $bid->subasta->ganador_id;
                    return $bid;
                });
            })
            ->flatten()
            ->sortByDesc('created_at');

        $perPage = $request->get('per_page', 10);
        $currentPage = $request->get('page', 1);
        $total = $bids->count();

        $paginatedBids = $bids->forPage($currentPage, $perPage)->values();

        $response = BidResource::collection($paginatedBids);
        $response->additional([
            'meta' => [
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
            ]
        ]);

        return $response;
    }
}
