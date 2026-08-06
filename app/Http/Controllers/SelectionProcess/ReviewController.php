<?php

namespace App\Http\Controllers\SelectionProcess;

use App\Domain\Projects\Services\ProjectService;
use App\Domain\Review\Services\ReviewService;
use App\Http\Controllers\Controller;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    public function notifyReviewer(SelectionProcess $selection, User $reviewer)
    {
        Gate::authorize('projects.manage');

        $reviewService = new ReviewService($selection);
        $reviewService->notifyReviewer($reviewer);

        return back();
    }

    public function notifyAll(SelectionProcess $selection)
    {
        Gate::authorize('projects.manage');

        $reviewService = new ReviewService($selection);
        $reviewService->notifyReviewers(onlyPending: true);

        return back();
    }

    public function destroyAll(SelectionProcess $selection)
    {
        Gate::authorize('projects.manage');

        $reviewService = new ReviewService($selection);
        $projectService = new ProjectService;
        $reviewService->returnDistributionStep($projectService);

        return back();
    }
}
