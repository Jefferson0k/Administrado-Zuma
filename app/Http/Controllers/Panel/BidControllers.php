<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bid\FiltradoRequest;
use App\Http\Resources\Subastas\Bid\BidResource;
use App\Models\Bid;
use Illuminate\Http\Request;

class BidControllers extends Controller{
    public function index(Request $request){
        $bids = Bid::with(['subasta', 'investor'])
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 10));

        return BidResource::collection($bids);
    }
}
