<?php

namespace App\Http\Controllers\Web\SubastaHipotecas;

use App\Http\Controllers\Controller;
use App\Models\TipoInmueble;
use Illuminate\Http\Request;

class TipoInmuebleController extends Controller
{
    public function index()
    {
        $tipos = TipoInmueble::orderBy('orden_tipo_inmueble')->get();
        return response()->json($tipos);
    }
}
