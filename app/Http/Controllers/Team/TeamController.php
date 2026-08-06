<?php

namespace App\Http\Controllers\Team;

use App\Domain\Front\Services\FrontIntegrationService;
use App\Domain\Projects\Types\ProjectModality;
use App\Domain\Review\Types\ReviewStatus;
use App\Domain\Shared\Types\UserRoles;
use App\Domain\Team\Services\RoleService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\TeamDeleteRequest;
use App\Http\Requests\Team\TeamStoreRequest;
use App\Http\Requests\Team\TeamUpdateRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\SelectionProcessResource;
use App\Http\Resources\TeamResource;
use App\Http\Resources\UserResource;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class TeamController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('users.manage');

        $selection = SelectionProcess::current()->first();

        $query = User::query()
            ->with('roles')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->sort, function ($query, $sort) use ($request) {
                $query->orderBy($sort, $request->direction ?? 'asc');
            }, function ($query) {
                $query->orderBy('name')->orderBy('email');
            });

        if ($selection) {
            $query->withCount(['reviewAssignments as assigned_count' => function ($query) use ($selection) {
                $query->whereIn('project_id', $selection->projects()->pluck('id'));
            }])
                ->withCount(['reviewAssignments as completed_count' => function ($query) use ($selection) {
                    $query->whereIn('project_id', $selection->projects()->pluck('id'))
                        ->whereHas('review', function ($query) {
                            $query->where('status', ReviewStatus::SUBMITTED);
                        });
                }]);
        }

        $users = $query->paginate()->withQueryString();

        return Inertia::render('team/List', [
            'users' => TeamResource::collection($users),
            'filters' => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    public function role(Request $request, string $role): Response
    {
        Gate::authorize('users.manage');

        $selection = SelectionProcess::current()->first();

        $query = User::role($role)->with('roles')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->sort, function ($query, $sort) use ($request) {
                $query->orderBy($sort, $request->direction ?? 'asc');
            }, function ($query) {
                $query->orderBy('name')->orderBy('email');
            });

        if ($selection) {
            $query->withCount(['reviewAssignments as assigned_count' => function ($query) use ($selection) {
                $query->whereIn('project_id', $selection->projects()->pluck('id'));
            }])
                ->withCount(['reviewAssignments as completed_count' => function ($query) use ($selection) {
                    $query->whereIn('project_id', $selection->projects()->pluck('id'))
                        ->whereHas('review', function ($query) {
                            $query->where('status', ReviewStatus::SUBMITTED);
                        });
                }]);
        }

        $users = $query->paginate()->withQueryString();

        return Inertia::render('team/List', [
            'users' => TeamResource::collection($users),
            'currentRole' => $role,
            'filters' => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('users.manage');

        return Inertia::render('team/Create', [
            'roles' => (new RoleService)->translate(Role::all()),
        ]);
    }

    public function store(TeamStoreRequest $request): RedirectResponse
    {
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Criado.',
        ]);

        $user = User::create($request->safe()->only(['name', 'email', 'password']));
        $user->markEmailAsVerified();
        $user->syncRoles($request->validated('roles', []));

        return to_route('team.show', [
            'user' => $user->id,
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        Gate::authorize('users.manage');

        $selection = auth()->user()->currentSelectionProcess;

        $projectsQuery = $selection->projects()
            ->with(['reviewAssignments.user'])
            ->where(function ($query) use ($user) {
                $query->whereHas('reviewAssignments', fn ($q) => $q->where('user_id', $user->id));

                if ($user->hasRole(UserRoles::MASTER_COMMITTEE->value)) {
                    $query->orWhere('modality', ProjectModality::MASTER->value);
                }

                if ($user->hasRole(UserRoles::DOCTORATE_COMMITTEE->value)) {
                    $query->orWhere('modality', ProjectModality::DOCTORATE->value);
                }
            });

        $projects = $projectsQuery
            ->when($request->search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('candidate_name', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->when($request->sort, function ($query, $sort) use ($request) {
                $query->orderBy($sort, $request->direction ?? 'asc');
            }, function ($query) {
                $query->orderBy('candidate_name');
            })
            ->paginate()
            ->withQueryString();

        return Inertia::render('team/Show', [
            'user' => new TeamResource($user->load('roles')),
            'selection' => new SelectionProcessResource($selection),
            'projects' => ProjectResource::collection($projects),
            'stats' => FrontIntegrationService::selectionProcessStatsByReviewed($selection, $user),
            'filters' => $request->only(['search', 'sort', 'direction']),
            'reviewers' => UserResource::collection(User::role(UserRoles::REVIEWER->value)->get()),
        ]);
    }

    public function edit(Request $request, User $user): Response
    {
        Gate::authorize('users.manage');

        return Inertia::render('team/Edit', [
            'user' => new TeamResource($user->load('roles')),
            'roles' => (new RoleService)->translate(Role::all()),
        ]);
    }

    public function update(TeamUpdateRequest $request, User $user): RedirectResponse
    {
        $user->update($request->safe()->only(['name', 'email']));
        $validated = $request->validated();

        if ($request->filled('password')) {
            $user->update(['password' => $validated['password']]);
        }

        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Atualizado.',
        ]);

        return to_route('team.edit', [
            'user' => $user->id,
        ]);
    }

    public function destroy(TeamDeleteRequest $request, User $user): RedirectResponse
    {
        $user->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Removido.',
        ]);

        return to_route('team.index');
    }
}
