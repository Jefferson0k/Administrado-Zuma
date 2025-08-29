<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class InvestorAvatarController extends Controller
{
    public function getAvatar(Request $request)
    {
        /** @var \App\Models\User $investor */
        $investor = Auth::user();

        // Optimización 1: Eager loading con select específico para evitar N+1 queries
        // Optimización 2: Usar selectRaw para obtener directamente los campos con alias camelCase
        $avatar = $investor->avatar()
            ->selectRaw('
                avatar_type,
                clothing_color,
                background_color,
                medal,
                medal_position,
                hat,
                hat_position,
                trophy,
                other
            ')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $avatar,
        ]);
    }
}
