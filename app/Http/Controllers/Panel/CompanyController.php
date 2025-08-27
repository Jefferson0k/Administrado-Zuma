<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\Factoring\Company\CompanyResource;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pipeline\Pipeline;

class CompanyController extends Controller{
    public function index(Request $request){
        Gate::authorize('viewAny', Company::class);
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $query = app(Pipeline::class)
            ->send(Company::query())
            ->through([
                
            ])
            ->thenReturn();
        return CompanyResource::collection($query->paginate($perPage));
    }
    public function store(){
        Gate::authorize('create', Company::class);
    }
    public function show(Company $company){
        Gate::authorize('view', $company);
    }
    public function update(Company $company){
        Gate::authorize('update', $company);
    }
    public function destroy(Company $company){
        Gate::authorize('delete', $company);
    }
}
