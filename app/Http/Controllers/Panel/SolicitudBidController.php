<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\SolicitudBid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SolicitudBidController extends Controller{
    public function store(Request $request){
        $request->validate([
            'solicitud_bid_id' => 'required|exists:solicitud_bids,id',
        ]);
        $investor = Auth::user();
        $investorId = $investor->id;
        if (!$investorId) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el inversionista autenticado.'
            ], 403);
        }
        $existe = Bid::where('solicitud_bid_id', $request->solicitud_bid_id)
            ->where('investors_id', $investorId)
            ->exists();

        if ($existe) {
            return response()->json([
                'success' => false,
                'message' => 'Ya has ofertado en esta solicitud.'
            ], 409);
        }
        $bid = Bid::create([
            'type' => 'solicitud',
            'solicitud_bid_id' => $request->solicitud_bid_id,
            'auction_id' => null,
            'investors_id' => $investorId,
        ]);
        $bid->load('investor');
        return response()->json([
            'success' => true,
            'message' => 'Oferta registrada correctamente.',
            'data' => $bid,
        ], 201);
    }
    public function storeSubasta(Request $request){
        $request->validate([
            'solicitud_bid_id' => 'required|exists:solicitud_bids,id',
        ]);
        $investor = Auth::user();
        $investorId = $investor->id;

        if (!$investorId) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el inversionista autenticado.'
            ], 403);
        }
        $solicitudBid = SolicitudBid::find($request->solicitud_bid_id);
        $auction = Auction::where('solicitud_id', $solicitudBid->solicitud_id)->first();
        if (!$auction) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró una subasta asociada a esta solicitud.'
            ], 404);
        }
        $existe = Bid::where('auction_id', $auction->id)
            ->where('investors_id', $investorId)
            ->exists();
        if ($existe) {
            return response()->json([
                'success' => false,
                'message' => 'Ya has ofertado en esta subasta.'
            ], 409);
        }
        $solicitudBidAsociado = SolicitudBid::where('solicitud_id', $auction->solicitud_id)
            ->whereNull('deleted_at')
            ->first();
        if (!$solicitudBidAsociado) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la relación SolicitudBid asociada a la subasta.'
            ], 404);
        }
        $bid = Bid::create([
            'type' => 'auction',
            'solicitud_bid_id' => $solicitudBidAsociado->id,
            'auction_id' => $auction->id,
            'investors_id' => $investorId,
        ]);
        $bid->load('investor');
        return response()->json([
            'success' => true,
            'message' => 'Oferta registrada correctamente.',
            'data' => $bid,
        ], 201);
    }
    public function getBySolicitudBid($solicitudBidId){
        $bids = Bid::where('solicitud_bid_id', $solicitudBidId)
            ->with('investor')
            ->get();
        return response()->json([
            'success' => true,
            'message' => 'Ofertas obtenidas correctamente.',
            'data' => $bids,
        ]);
    }
}
