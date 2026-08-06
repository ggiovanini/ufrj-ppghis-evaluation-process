<?php

namespace App\Http\Controllers\Reviews;

use App\Domain\Front\Services\FrontIntegrationService;
use App\Domain\Review\Notifications\ReviewPdfReadyNotification;
use App\Domain\Review\Services\ReviewPdfService;
use App\Domain\Review\Services\ReviewService;
use App\Domain\Review\Types\ReviewScore;
use App\Domain\Review\Types\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\SelectionProcessResource;
use App\Models\Project;
use App\Models\Review;
use App\Models\SelectionProcess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EvaluateController extends Controller
{
    public function index(Request $request, SelectionProcess $selection): Response
    {
        Gate::authorize('review.view-own');

        $query = $selection->projects()
            ->whereHas('reviewAssignments', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->with(['reviewAssignments' => function ($query) {
                $query->where('user_id', auth()->id())->with('review.reviewForm', 'review.reviewAssignment.project');
            }]);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('candidate_name', 'like', '%'.$request->search.'%')
                    ->orWhere('title', 'like', '%'.$request->search.'%');
            });
        }

        $sort = $request->input('sort', 'candidate_name');
        $direction = $request->input('direction', 'asc');

        if ($sort === 'status') {
            $query->select('projects.*')
                ->join('review_assignments', function ($join) {
                    $join->on('projects.id', '=', 'review_assignments.project_id')
                        ->where('review_assignments.user_id', '=', auth()->id());
                })
                ->leftJoin('reviews', 'review_assignments.id', '=', 'reviews.review_assignment_id')
                ->orderByRaw("CASE
                    WHEN reviews.status IS NULL THEN 1
                    WHEN reviews.status = 'draft' THEN 2
                    WHEN reviews.status = 'submitted' THEN 3
                    ELSE 4 END ".$direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        $projects = $query->paginate()->withQueryString();

        return Inertia::render('reviews/Index', [
            'selection' => new SelectionProcessResource($selection),
            'projects' => ProjectResource::collection($projects),
            'filters' => $request->only(['search', 'sort', 'direction']),
            'phases' => FrontIntegrationService::selectionProcessPhases(),
            'stats' => FrontIntegrationService::selectionProcessStatsByReviewed($selection, auth()->user()),
        ]);
    }

    public function store(Request $request, SelectionProcess $selection, Project $project)
    {
        Gate::authorize('review.view-own');

        $assignment = $project->reviewAssignments()
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($assignment->review?->status === ReviewStatus::SUBMITTED) {
            return back()->with('error', 'Esta avaliação já foi submetida e não pode ser alterada.');
        }

        $rules = [
            'score' => ['nullable', Rule::enum(ReviewScore::class)],
            'comments' => 'nullable|string',
            'questions' => 'nullable|string',
            'status' => ['required', Rule::enum(ReviewStatus::class)],
        ];

        if ($request->input('status') === ReviewStatus::SUBMITTED->value) {
            $rules['score'] = ['required', Rule::enum(ReviewScore::class), 'not_in:'.ReviewScore::PENDENT->value];

            $formSchema = $selection->reviewForm?->schema['fields'] ?? [];
            foreach ($formSchema as $field) {
                if ($field['required'] ?? false) {
                    $rules['answers.'.$field['id']] = ['required'];
                }
            }
        }

        $validated = $request->validate($rules);

        $reviewService = new ReviewService($selection);
        $reviewService->update($assignment, $validated, $request->input('answers', []));

        if ($validated['status'] === ReviewStatus::SUBMITTED->value) {
            $review = $assignment->review()->with([
                'reviewAssignment.project',
                'reviewAssignment.user',
                'reviewForm',
            ])->firstOrFail();

            (new ReviewPdfService)->generate($review);
            $assignment->user->notify(new ReviewPdfReadyNotification($review));
        }

        $message = $validated['status'] === ReviewStatus::SUBMITTED->value
            ? 'Avaliação enviada com sucesso!'
            : 'Rascunho salvo com sucesso!';

        return back()->with('success', $message);
    }

    public function downloadPdf(SelectionProcess $selection, Review $review)
    {
        Gate::authorize('review.view-own');

        $review->load('reviewAssignment.project');
        $assignment = $review->reviewAssignment;

        abort_unless(
            $assignment
                && $assignment->user_id === auth()->id()
                && $assignment->project?->selection_process_id === $selection->id
                && $review->pdf_path
                && Storage::disk('local')->exists($review->pdf_path),
            404,
        );

        return Storage::disk('local')->download(
            $review->pdf_path,
            'avaliacao-'.$review->id.'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }
}
