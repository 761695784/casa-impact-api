<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $perPage = min((int) $request->integer('per_page', 15), 100);

        $users = User::query()
            ->with('roles')
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->string('search')}%")
                    ->orWhere('email', 'like', "%{$request->string('search')}%");
            }))
            ->orderBy('name')
            ->paginate($perPage);

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);

        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->syncRoles($data['roles']);

        return (new UserResource($user->load('roles')))
            ->additional(['message' => 'Utilisateur créé avec succès.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);

        return new UserResource($user->load('roles'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        // Réaffirmation explicite de la règle métier (déjà validée dans le
        // Form Request, mais un contrôleur ne doit jamais supposer qu'un appel
        // amont a nécessairement été respecté — défense en profondeur).
        if ($request->filled('roles') && $request->user()->is($user)) {
            abort(403, "Vous ne pouvez pas modifier votre propre rôle.");
        }

        $data = $request->validated();

        $user->fill([
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        if (! empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return (new UserResource($user->load('roles')))
            ->additional(['message' => 'Utilisateur mis à jour avec succès.']);
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorize('delete', $user);

        // Réaffirmation explicite (voir UserPolicy::delete) : personne ne
        // supprime son propre compte, quel que soit le rôle.
        if ($request->user()->is($user)) {
            abort(403, "Vous ne pouvez pas supprimer votre propre compte.");
        }

        $user->delete(); // soft delete

        return response()->json(['message' => 'Utilisateur supprimé avec succès.']);
    }
}
