<?php

namespace App\Http\Controllers;

use App\Models\TipoDocumento;
use Illuminate\Http\Request;

class TipoDocumentoController extends Controller
{
    public function index()
    {
        return response()->json(TipoDocumento::orderBy('orden_tipo_documento')->get());
    }
}
