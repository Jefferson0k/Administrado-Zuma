<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bid\FiltradoRequest;
use App\Http\Resources\Subastas\Bid\BidResource;
use App\Models\Bid;

class BidControllers extends Controller{
    public function index(FiltradoRequest $request){
        $auction_id = $request->validated('auction_id');
        $perPage = $request->validated('per_page') ?? 15;
        $bids = Bid::where('auction_id', $auction_id)
            ->with('usuario')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
        return BidResource::collection($bids);
    }
}
