<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\Subastas\Bid\BidResource;
use App\Models\Bid;
use Illuminate\Http\Request;

class BidControllers extends Controller{
    public function index(Request $request){
        $type = $request->get('type', 'solicitud');
        $perPage = (int) $request->get('per_page', 10);
        $currentPage = (int) $request->get('page', 1);
        $bids = Bid::with([
            'investor',
            'subasta.ganador',
            'subasta.solicitud',
            'solicitudBid.solicitud',
        ])->where('type', $type)->get();
        if ($type === 'subasta') {
            $bids = $bids->filter(fn($b) => $b->subasta)
                ->map(function ($bid) {
                    $bid->puesto = null;
                    $bid->es_ganador = $bid->subasta->ganador_id === $bid->investors_id;
                    return $bid;
                });
        }
        $bids = $bids->values();
        $total = $bids->count();
        $paginated = $bids->forPage($currentPage, $perPage)->values();
        $response = BidResource::collection($paginated);
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
