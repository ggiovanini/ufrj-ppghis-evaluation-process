<?php

use App\Http\Controllers\Projects\ProjectsController;
use App\Http\Controllers\Reviews\EvaluateController;
use App\Http\Controllers\SelectionProcess\ReviewAssignmentController;
use App\Http\Controllers\SelectionProcess\ReviewController;
use App\Http\Controllers\SelectionProcess\SelectionProcessController;
use App\Http\Controllers\SelectionProcess\SelectionProcessDocumentsController;
use App\Http\Controllers\SelectionProcess\UserSelectionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('selection')
    ->name('selection')
    ->group(function () {
        Route::get('/', [SelectionProcessController::class, 'index'])->name('.index');
        Route::get('/documents', [SelectionProcessDocumentsController::class, 'index'])->name('.documents.index');
        Route::get('/create', [SelectionProcessController::class, 'create'])->name('.create');
        Route::post('/create', [SelectionProcessController::class, 'store'])->name('.store');
        Route::post('/select', [UserSelectionController::class, 'update'])->name('.select');
        Route::get('/{selection}', [SelectionProcessController::class, 'show'])->name('.show');
        Route::get('/{selection}/edit', [SelectionProcessController::class, 'edit'])->name('.edit');
        Route::patch('/{selection}', [SelectionProcessController::class, 'update'])->name('.update');
        Route::delete('/{selection}', [SelectionProcessController::class, 'destroy'])->name('.delete');
        Route::get('/{selection}/prepare', [SelectionProcessController::class, 'prepare'])->name('.prepare');
        Route::post('/{selection}/import', [SelectionProcessController::class, 'import'])->name('.import');
        Route::post('/{selection}/finalize', [SelectionProcessController::class, 'finalize'])->name('.finalize');
        Route::post('/{selection}/results/recalculate', [SelectionProcessController::class, 'recalculateResults'])->name('.results.recalculate');
        Route::post('/{selection}/return-to-homologation', [SelectionProcessController::class, 'returnToHomologation'])->name('.return-to-homologation');
        Route::post('/{selection}/homologation/approve-all-and-finalize', [SelectionProcessController::class, 'approveAllAndFinalize'])->name('.homologation.approve-all-and-finalize');

        Route::post('/{selection}/reviews/notify', [ReviewController::class, 'notifyAll'])->name('.reviews.notify-all');
        Route::post('/{selection}/reviews/notify/{reviewer}', [ReviewController::class, 'notifyReviewer'])->name('.reviews.notify-reviewer');
        Route::get('/{selection}/reviews/{review}/pdf', [EvaluateController::class, 'downloadPdf'])->name('.reviews.pdf');
        Route::delete('/{selection}/reviews', [ReviewController::class, 'destroyAll'])->name('.reviews.destroy-all');

        Route::post('/{selection}/assignments/auto', [ReviewAssignmentController::class, 'autoAssign'])->name('.assignments.auto');
        Route::post('/{selection}/assignments/auto/complete', [ReviewAssignmentController::class, 'autoAssignComplete'])->name('.assignments.auto.complete');
        Route::post('/{selection}/assignments', [ReviewAssignmentController::class, 'store'])->name('.assignments.store');
        Route::delete('/{selection}/assignments', [ReviewAssignmentController::class, 'destroyAll'])->name('.assignments.destroy-all');
        Route::delete('/{selection}/assignments/single', [ReviewAssignmentController::class, 'destroy'])->name('.assignments.destroy');

        Route::get('/{selection}/evaluate', [EvaluateController::class, 'index'])->name('.evaluate');
        Route::post('/{selection}/evaluate/{project}', [EvaluateController::class, 'store'])->name('.evaluate.store');

        Route::prefix('/{selection}/projects')
            ->name('.projects')
            ->group(function () {
                Route::get('/', [ProjectsController::class, 'index'])->name('.index');
                Route::delete('/', [ProjectsController::class, 'destroyAll'])->name('.delete-all');
                Route::get('/homologation-report', [ProjectsController::class, 'homologationReport'])->name('.homologation.report');
                Route::get('/distribution-report', [ProjectsController::class, 'distributionReport'])->name('.distribution.report');
                Route::get('/review-report', [ProjectsController::class, 'reviewReport'])->name('.review.report');
                Route::get('/written-exam-report', [ProjectsController::class, 'writtenExamReport'])->name('.written-exam.report');
                Route::get('/committee-report', [ProjectsController::class, 'committeeReport'])->name('.committee.report');
                Route::get('/final-result-report', [ProjectsController::class, 'finalResultReport'])->name('.final-result.report');
                Route::get('/{project}', [ProjectsController::class, 'show'])->name('.show');
                Route::patch('/{project}/homologation', [SelectionProcessController::class, 'updateHomologation'])->name('.homologation.update');
                Route::get('/{project}/edit', [ProjectsController::class, 'edit'])->name('.edit');
                Route::patch('/{project}', [ProjectsController::class, 'update'])->name('.update');
                Route::patch('/{project}/committee-score', [ProjectsController::class, 'updateCommitteeScore'])->name('.committee-score.update');
                Route::delete('/{project}', [ProjectsController::class, 'destroy'])->name('.delete');
                Route::delete('/{project}/assignments', [ReviewAssignmentController::class, 'destroyForProject'])->name('.assignments.destroy');
            });
    });
