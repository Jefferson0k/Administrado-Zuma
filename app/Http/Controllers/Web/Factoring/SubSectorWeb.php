<?php

namespace App\Http\Controllers\Web\Factoring;

use App\Http\Controllers\Controller;
use App\Http\Resources\Factoring\SubSector\SubSectorResource;
use App\Models\Subsector;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SubSectorWeb extends Controller{
    public function views($id): Response{
        Gate::authorize('viewAny', Subsector::class);
        $subsector = Subsector::with('sector')->findOrFail($id);
        $subsectorResource = new SubSectorResource($subsector);
        return Inertia::render('panel/Factoring/SubSector/indexSubSector', [
            'sector' => $subsectorResource,
        ]);
    }
}
