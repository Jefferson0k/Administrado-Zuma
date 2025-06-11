<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InvoiceController extends Controller{
     public function index(Request $request){
        $perPage = $request->input('per_page', 15);
        $query = Invoice::query();
        return InvoiceResource::collection($query->paginate($perPage));
    }
}
