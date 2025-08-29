<?php

namespace App\Http\Controllers\Web\Factoring;
use App\Http\Controllers\Controller;
use App\Models\Sector;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
class SectorWeb extends Controller{
    public function views(): Response{
        Gate::authorize('viewAny', Sector::class);
        return Inertia::render('panel/Factoring/Sector/indexSector');
    }
}
