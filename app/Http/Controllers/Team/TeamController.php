<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\TeamDeleteRequest;
use App\Http\Requests\Team\TeamStoreRequest;
use App\Http\Requests\Team\TeamUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('team/List');
    }

    public function role(Request $request): Response
    {
        return Inertia::render('team/ListByRole');
    }

    public function create(Request $request): Response
    {
        return Inertia::render('team/Create');
    }

    public function store(TeamStoreRequest $request): RedirectResponse
    {
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Criado.'
        ]);

        $user = User::create($request->validated(['name', 'email', 'password']));
        $user->markEmailAsVerified();
        $validated = $request->validated(['roles']);
        $user->syncRoles($validated['roles']);

        return to_route('team.show', [
            'team' => $user->id
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        return Inertia::render('team/Show', [
            'user' => $user
        ]);
    }

    public function edit(Request $request, User $user): Response
    {
        return Inertia::render('team/Edit', [
            'user' => $user
        ]);
    }

    public function update(TeamUpdateRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated(['name', 'email']));
        $validated = $request->validated(['roles']);

        if ($validated['password'])
            $user->update($request->validated(['password']));

        if ($validated['roles'] !== $user->hasExactRoles($validated['roles']))
            $user->syncRoles([$validated['roles']]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Atualizado.'
        ]);

        return to_route('team.edit', [
            'team' => $user->id
        ]);
    }

    public function destroy(TeamDeleteRequest $request, User $user): RedirectResponse
    {
        $user->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Removido.'
        ]);

        return redirect('team.index');
    }
}
