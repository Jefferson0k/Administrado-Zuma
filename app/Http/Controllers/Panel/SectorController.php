<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sector\StoreSectorRequest;
use App\Http\Requests\Sector\UpdateSectorRequest;
use App\Http\Resources\Factoring\Sector\SearchSectorResource;
use App\Http\Resources\Factoring\Sector\SectorResource;
use App\Models\Company;
use App\Models\Sector;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Throwable;

class SectorController extends Controller{
    public function index(){
        try {
            Gate::authorize('viewAny', Sector::class);
            $sectors = Sector::all();
            return response()->json([
                'total' => $sectors->count(),
                'data'  => SectorResource::collection($sectors),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'No tienes permiso para ver los sectores.'
            ], 403);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al listar los sectores.'
            ], 500);
        }
    }
    public function store(StoreSectorRequest $request){
        try {
            Gate::authorize('create', Sector::class);
            $data = $request->validated();
            $data['created_by'] = Auth::id();
            $sector = Sector::create($data);
            return response()->json([
                'message' => 'Sector creado correctamente.',
                'data'    => new SectorResource($sector)
            ], 201);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para crear un sector.'], 403);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Error al crear el sector.'], 500);
        }
    }
    public function show($id){
        try {
            $sector = Sector::findOrFail($id);
            Gate::authorize('view', $sector);
            return new SectorResource($sector);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Sector no encontrada.'], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para ver este Sector.'], 403);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Error al mostrar el Sector.'], 500);
        }
    }
    public function update(UpdateSectorRequest $request, $id){
        try {
            $sector = Sector::findOrFail($id);
            Gate::authorize('update', $sector);
            $data = $request->validated();
            $data['updated_by'] = Auth::id();
            $sector->update($data);
            return response()->json([
                'message' => 'Sector actualizado correctamente.',
                'data'    => new SectorResource($sector)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Sector no encontrado.'], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para editar este sector.'], 403);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Error al actualizar el sector.'], 500);
        }
    }
    public function delete($id){
        try {
            $sector = Sector::findOrFail($id);
            Gate::authorize('delete', $sector);
            $sector->deleted_by = Auth::id();
            $sector->save();
            $sector->delete();
            return response()->json(['message' => 'Sector eliminada correctamente.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Sector no encontrada.'], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para eliminar esta Sector.'], 403);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Error al eliminar la Sector.'], 500);
        }
    }
    public function searchSector(){
        try {
            Gate::authorize('search', Company::class);
            $sectors = Sector::all();
            return response()->json([
                'data' => SearchSectorResource::collection($sectors),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'No tienes permiso para buscar sectores.'
            ], 403);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al listar los sectores.'
            ], 500);
        }
    }
}

