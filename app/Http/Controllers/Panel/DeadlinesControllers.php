<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\Subastas\Deadlines\DeadlinesResource;
use App\Models\Deadlines;

class DeadlinesControllers extends Controller{
    public function index(){
        return DeadlinesResource::collection(Deadlines::all());
    }
}
