<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exchange\StoreExchangeRequest;
use App\Http\Requests\Exchange\UpdateExchangeRequest;
use App\Http\Resources\Factoring\Exchange\ExchangeResource;
use App\Models\Exchange;
use App\Models\Movement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ExchangeController extends Controller{
    public function index(): JsonResponse{
        Gate::authorize('viewAny', Exchange::class);
        $exchanges = Exchange::latest()->paginate(10);
        return response()->json(ExchangeResource::collection($exchanges));
    }
    public function show($id): JsonResponse{
        $exchange = Exchange::findOrFail($id);
        Gate::authorize('view', $exchange);
        return response()->json(new ExchangeResource($exchange));
    }
    public function store(StoreExchangeRequest $request): JsonResponse{
        Gate::authorize('create', Exchange::class);
        $exchange = Exchange::create(array_merge(
            [
                'currency' => 'USD',
                'status' => 'inactive',
            ],
            $request->validated()
        ));
        return response()->json([
            'message' => 'Tipo de cambio creado correctamente',
            'data' => new ExchangeResource($exchange),
        ]);
    }
    public function update(UpdateExchangeRequest $request, $id): JsonResponse{
        $exchange = Exchange::findOrFail($id);
        Gate::authorize('update', $exchange);
        $exchange->update($request->validated());
        return response()->json([
            'message' => 'Tipo de cambio actualizado correctamente',
            'data'    => new ExchangeResource($exchange),
        ]);
    }
    public function destroy($id): JsonResponse{
        $exchange = Exchange::findOrFail($id);
        Gate::authorize('delete', $exchange);
        $exchange->delete();
        return response()->json([
            'message' => 'Tipo de cambio eliminado correctamente',
        ]);
    }
    public function activacion($id): JsonResponse{
        $exchange = Exchange::findOrFail($id);
        Gate::authorize('update', $exchange);
        $exchange->forceFill([
            'status' => 'active',
        ])->save();
        return response()->json([
            'message' => 'Tipo de cambio activado correctamente',
            'data'    => new ExchangeResource($exchange),
        ]);
    }
    public function inactivo($id): JsonResponse{
        $exchange = Exchange::findOrFail($id);
        Gate::authorize('update', $exchange);
        $exchange->forceFill([
            'status' => 'inactive',
        ])->save();
        return response()->json([
            'message' => 'Tipo de cambio inactivado correctamente',
            'data'    => new ExchangeResource($exchange),
        ]);
    }
    public function indexList(Request $request){
        $movements = Movement::with(['investor:id,name,email'])
            ->whereIn('type', ['exchange', 'exchange_up', 'exchange_down']) // solo los de tipo cambio
            ->orderBy('created_at', 'desc')
            ->paginate(10) // puedes ajustar el número de items por página
            ->withPath('')
            ->setPageName($request->input('pageName') ?: 'page');

        return response()->json([
            'success' => true,
            'data' => $movements,
            'message' => null
        ]);
    }

}
