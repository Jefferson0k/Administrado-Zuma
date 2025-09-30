<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubSectors\StoreSubSectorRequest;
use App\Http\Requests\SubSectors\UpdateSubSectorRequest;
use App\Http\Resources\Factoring\SubSector\SearchSubSectorResource;
use App\Http\Resources\Factoring\SubSector\SubSectorResource;
use App\Models\Company;
use App\Models\Subsector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Throwable;

class SubSectorController extends Controller{
    public function index($sector_id){
        try {
            Gate::authorize('viewAny', Subsector::class);
            $subsectors = Subsector::where('sector_id', $sector_id)
                ->withCount(['companies as vinculado' => function ($q) {
                    $q->select(DB::raw('CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END'));
                }])
                ->get();
            return response()->json([
                'total' => $subsectors->count(),
                'data'  => SubSectorResource::collection($subsectors),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para ver los subsectores.'], 403);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Error al listar los subsectores.'], 500);
        }
    }

    public function store(StoreSubSectorRequest $request){
        try {
            Gate::authorize('create', Subsector::class);
            $data = $request->validated();
            $data['created_by'] = Auth::id();
            $subsector = Subsector::create($data);
            return response()->json([
                'message' => 'Subsector creado correctamente.',
                'data' => new SubsectorResource($subsector)
            ], 201);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para crear un subsector.'], 403);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Error al crear el subsector.'], 500);
        }
    }
    public function show($id){
        try {
            $subsector = Subsector::findOrFail($id);
            Gate::authorize('view', $subsector);
            return new SubsectorResource($subsector);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Subsector no encontrado.'], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para ver este subsector.'], 403);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Error al mostrar el subsector.'], 500);
        }
    }
    public function update(UpdateSubSectorRequest $request, $id){
        try {
            $subsector = Subsector::findOrFail($id);
            Gate::authorize('update', $subsector);
            $data = $request->validated();
            $data['updated_by'] = Auth::id();
            $subsector->update($data);
            return response()->json([
                'message' => 'Subsector actualizado correctamente.',
                'data' => new SubsectorResource($subsector)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Subsector no encontrado.'], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para editar este subsector.'], 403);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Error al actualizar el subsector.'], 500);
        }
    }

    public function delete($id){
        try {
            $subsector = Subsector::findOrFail($id);
            Gate::authorize('delete', $subsector);
            $subsector->deleted_by = Auth::id();
            $subsector->save();
            $subsector->delete();
            return response()->json(['message' => 'Subsector eliminado correctamente.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Subsector no encontrado.'], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para eliminar este subsector.'], 403);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Error al eliminar el subsector.'], 500);
        }
    }
    public function searchSubSector($sectorId){
        try {
            Gate::authorize('viewAny', Company::class);
            $subsectors = Subsector::where('sector_id', $sectorId)->get();
            return response()->json([
                'data' => SearchSubSectorResource::collection($subsectors),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'No tienes permiso para buscar subsectores.'
            ], 403);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al listar los subsectores.'
            ], 500);
        }
    }
}
