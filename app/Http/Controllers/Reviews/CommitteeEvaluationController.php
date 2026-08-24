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

class CommitteeEvaluationController extends Controller
{
    public function index(Request $request, SelectionProcess $selection): Response
    {
        Gate::authorize('committee.evaluate');

        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $modality = false;
        if ($user->hasRole(UserRoles::MASTER_COMMITTEE->value)) {
            $modality = ProjectModality::MASTER;
        } elseif ($user->hasRole(UserRoles::DOCTORATE_COMMITTEE->value)) {
            $modality = ProjectModality::DOCTORATE;
        }
        abort_unless($modality, 403);

        $projects = $selection->projects()
            ->where('stage', ProjectStage::COMMITTEE)
            ->where('modality', $modality)
            ->whereHas('committeeEvaluation')
            ->with('committeeEvaluation')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('candidate_name', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->when($request->sort === 'status', function ($query) use ($request) {
                $query->orderByRaw('CASE WHEN committee_score IS NULL THEN 1 ELSE 2 END '.($request->direction ?? 'asc'));
            }, function ($query) use ($request) {
                $query->orderBy($request->input('sort', 'candidate_name'), $request->input('direction', 'asc'));
            })
            ->paginate()
            ->withQueryString();

        return Inertia::render('committee/Index', [
            'selection' => new SelectionProcessResource($selection),
            'projects' => ProjectResource::collection($projects),
            'filters' => $request->only(['search', 'sort', 'direction']),
            'phases' => FrontIntegrationService::selectionProcessPhases(),
            'stats' => FrontIntegrationService::selectionProcessStatsByReviewed($selection, $user),
        ]);
    }
}
