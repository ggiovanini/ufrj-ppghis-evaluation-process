<?php

namespace App\Http\Controllers\Reviews;

use App\Domain\Front\Services\FrontIntegrationService;
use App\Domain\Projects\Types\ProjectModality;
use App\Domain\Projects\Types\ProjectStage;
use App\Domain\Shared\Types\UserRoles;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\SelectionProcessResource;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WrittenExamController extends Controller
{
    public function index(Request $request, SelectionProcess $selection): Response
    {
        Gate::authorize('committee.evaluate');

        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless($user->hasRole(UserRoles::MASTER_COMMITTEE->value), 403);

        $projects = $selection->projects()
            ->where('modality', ProjectModality::MASTER)
            ->where('stage', ProjectStage::WRITTEN_EXAM)
            ->with('writtenExam')
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

        return Inertia::render('written-exam/Index', [
            'selection' => new SelectionProcessResource($selection),
            'projects' => ProjectResource::collection($projects),
            'filters' => $request->only(['search', 'sort', 'direction']),
            'phases' => FrontIntegrationService::selectionProcessPhases(),
            'stats' => FrontIntegrationService::selectionProcessStatsByReviewed($selection, $user),
        ]);
    }
}
