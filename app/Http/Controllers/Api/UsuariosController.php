<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Usuario\StoreUserRequest;
use App\Http\Requests\Usuario\UpdateUserRequest;
use App\Http\Resources\Usuario\UserResource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UsuariosController extends Controller{
    public function index(Request $request){
        Gate::authorize('viewAny', User::class);
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search', '');
        $estado = $request->input('status');
        $onlineStatus = $request->input('online');
        $query = User::query();
        if (!empty($search)) {
            $normalizedSearch = strtolower(trim(preg_replace('/\s+/', ' ', $search)));
            $searchTerms = explode(' ', $normalizedSearch);
            $query->where(function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->orWhere(function ($subQuery) use ($term) {
                        $subQuery->where('name', 'ILIKE', '%' . $term . '%')
                            ->orWhere('email', 'ILIKE', '%' . $term . '%')
                            ->orWhere('dni', 'ILIKE', '%' . $term . '%')
                            ->orWhere('apellidos', 'ILIKE', '%' . $term . '%')
                            ->orWhere('nacimiento', 'ILIKE', '%' . $term . '%')
                            ->orWhere('status', 'ILIKE', '%' . $term . '%')
                            ->orWhere('username', 'ILIKE', '%' . $term . '%');
                    });
                }
            });
        }
        if (isset($estado)) {
            $query->where('status', $estado);
        }
        if (isset($onlineStatus)) {
            if ($onlineStatus == '1') {
                $query->whereIn('id', function ($subquery) {
                    $subquery->select('id')->from('users');
                });
            } elseif ($onlineStatus == '0') {
                $query->whereNotIn('id', function ($subquery) {
                    $subquery->select('id')->from('users');
                });
            }
        }
        $users = $query->paginate($perPage);
        return UserResource::collection($users);
    }
    public function store(StoreUserRequest $request){
        Gate::authorize('create', User::class);
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
        $validated['restablecimiento'] = 0;
        $validated['created_by'] = Auth::id();
        $user = User::create($validated);
        if ($request->filled('role_id')) {
            $role = Role::find($request->input('role_id'));
            if ($role) {
                $user->assignRole($role->name);
            }
        }
        return response()->json($user);
    }
    public function show(User $user){
        Gate::authorize('view', $user);
        return response()->json([
            'status' => true,
            'message' => 'Usuario encontrado',
            'user' => new UserResource($user),
        ], 200);
    }
    public function update(UpdateUserRequest $request, User $user){
        Gate::authorize('update', $user);
        $data = $request->validated();
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
            $data['restablecimiento'] = 0;
        } else {
            unset($data['password']);
        }
        $data['updated_by'] = Auth::id();

        $user->update($data);

        if ($request->filled('role_id')) {
            $newRole = Role::find($request->input('role_id'));
            if ($newRole) {
                $user->syncRoles([$newRole->name]);
            }
        }
        return response()->json([
            'status' => true,
            'message' => 'Usuario actualizado correctamente',
            'user' => new UserResource($user->refresh()),
        ]);
    }

    public function destroy(User $user){
        Gate::authorize('delete', $user);
        $user->update([
            'deleted_by' => Auth::id(),
        ]);
        $user->delete();
        return response()->json([
            'status' => true,
            'message' => 'Usuario eliminado correctamente'
        ]);
    }
}
