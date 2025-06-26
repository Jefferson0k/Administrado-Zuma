<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\Subastas\Currency\CurrencyResource;
use App\Models\Currency;

class CurrencyControllers extends Controller{
    public function index(){
        return CurrencyResource::collection(Currency::all());
    }
}
