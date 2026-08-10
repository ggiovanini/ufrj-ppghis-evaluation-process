<?php

namespace App\Domain\Front\Services;

use App\Domain\Projects\Types\ProjectHomologationStatus;
use App\Domain\Projects\Types\ProjectModality;
use App\Domain\Projects\Types\ProjectStage;
use App\Domain\Review\Types\ReviewScore;
use App\Domain\Review\Types\ReviewStatus;
use App\Domain\SelectionProcess\Types\SelectionProcessPhases;
use App\Domain\Shared\Types\UserRoles;
use App\Models\Review;
use App\Models\ReviewAssignment;
use App\Models\SelectionProcess;
use App\Models\User;

class FrontIntegrationService
{
    public static function selectionProcessStats(SelectionProcess $selection): array
    {
        $allProjects = $selection->projects();
        $approvedProjects = $selection->projects()
            ->where('homologation_status', ProjectHomologationStatus::APPROVED);
        $projects = clone $allProjects;

        if (in_array($selection->phase, [
            SelectionProcessPhases::DISTRIBUTION,
            SelectionProcessPhases::REVIEW,
            SelectionProcessPhases::WRITTEN_EXAM,
            SelectionProcessPhases::COMMITTEE,
            SelectionProcessPhases::RESULTS,
            SelectionProcessPhases::FINISHED,
        ], true)) {
            $projects = clone $approvedProjects;
        }

        $homologationTotal = (clone $allProjects)->count();
        $homologationApproved = (clone $approvedProjects)->count();
        $homologationRejected = (clone $allProjects)
            ->where('homologation_status', ProjectHomologationStatus::REJECTED)
            ->count();
        $totalAssigned = (clone $projects)->has('reviewAssignments', '>=', 3)->count();
        $totalProjectReviewed = (clone $projects)->whereHas('reviewAssignments', function ($query) {
            $query->whereHas('review', function ($query) {
                $query->where('status', ReviewStatus::SUBMITTED);
            });
        }, '>=', 3)->count();
        $totalProjectReviewedAndRejected = (clone $projects)->whereHas('reviewAssignments', function ($query) {
            $query->whereHas('review', function ($query) {
                $query->where('status', ReviewStatus::SUBMITTED);
            });
        }, '>=', 3)->where('stage', ProjectStage::REJECTED)->count();
        $totalProjectReviewedAndPassed = (clone $projects)->whereHas('reviewAssignments', function ($query) {
            $query->whereHas('review', function ($query) {
                $query->where('status', ReviewStatus::SUBMITTED);
            });
        }, '>=', 3)->whereNot('stage', ProjectStage::REJECTED)->count();
        $committeeEvaluations = (clone $approvedProjects)
            ->whereHas('committeeEvaluation');
        $writtenExamsTotal = (clone $approvedProjects)
            ->where('modality', ProjectModality::MASTER->value)
            ->whereHas('writtenExam')
            ->count();
        $writtenExamsCompleted = (clone $approvedProjects)
            ->whereHas('writtenExam', fn ($query) => $query->whereNotNull('score'))
            ->where('modality', ProjectModality::MASTER->value)
            ->count();
        $writtenExamsPassed = (clone $approvedProjects)
            ->whereHas('writtenExam', fn ($query) => $query->whereNotNull('score')->where('passed', true))
            ->where('modality', ProjectModality::MASTER->value)
            ->count();
        $writtenExamsFailed = (clone $approvedProjects)
            ->whereHas('writtenExam', fn ($query) => $query->whereNotNull('score')->where('passed', false))
            ->where('modality', ProjectModality::MASTER->value)
            ->count();
        $committeeEvaluationsTotal = $committeeEvaluations->count();
        $committeeEvaluationsCompleted = (clone $committeeEvaluations)
            ->whereNotNull('committee_score')
            ->count();
        $finalResults = (clone $committeeEvaluations)->whereHas('finalResults');

        return [
            'total_projects' => $projects->count(),
            'total_assigned' => $totalAssigned,
            'total_reviewed' => Review::whereHas('reviewAssignment.project', function ($query) use ($selection) {
                $query->where('selection_process_id', $selection->id)
                    ->where('homologation_status', ProjectHomologationStatus::APPROVED);
            })->where('status', ReviewStatus::SUBMITTED)->count(),
            'total_reviews' => Review::whereHas('reviewAssignment.project', function ($query) use ($selection) {
                $query->where('selection_process_id', $selection->id)
                    ->where('homologation_status', ProjectHomologationStatus::APPROVED);
            })->count(),
            'total_project_reviewed' => $totalProjectReviewed,
            'total_project_reviews' => $projects->count(),
            'written_exams' => $writtenExamsTotal,
            'written_examined' => $writtenExamsCompleted,
            'written_exam_passed' => $writtenExamsPassed,
            'written_exam_failed' => $writtenExamsFailed,
            'committee_evaluations' => $committeeEvaluationsTotal,
            'committee_evaluated' => $committeeEvaluationsCompleted,
            'homologation_total' => $homologationTotal,
            'homologation_revised' => $homologationApproved + $homologationRejected,
            'homologation_rejected' => $homologationRejected,
            'homologation_accepted' => $homologationApproved,
            'distribution_not_passed' => max(0, $homologationApproved - $totalAssigned),
            'review_not_passed' => $totalProjectReviewedAndRejected,
            'review_passed' => $totalProjectReviewedAndPassed,
            'written_exam_not_passed' => max(0, $writtenExamsTotal - $writtenExamsCompleted),
            'committee_not_passed' => (clone $committeeEvaluations)->whereHas('committeeEvaluation', function ($query) {
                $query->where('passed', false);
            })->count(),
            'committee_passed' => (clone $committeeEvaluations)->whereHas('committeeEvaluation', function ($query) {
                $query->where('passed', true);
            })->count(),
            'final_results' => $committeeEvaluationsTotal,
            'final_resulted' => $finalResults->count(),
        ];
    }

    public static function selectionProcessStatsByReviewed(SelectionProcess $selection, User $user): array
    {
        $isReviewer = $user->hasRole(UserRoles::REVIEWER->value);
        $isMasterCommittee = $user->hasRole(UserRoles::MASTER_COMMITTEE->value);
        $isDoctorateCommittee = $user->hasRole(UserRoles::DOCTORATE_COMMITTEE->value);

        $projectsQuery = $selection->projects();
        if ($isReviewer) {
            $projectsQuery->whereHas('reviewAssignments', fn ($q) => $q->where('user_id', $user->id));
        } elseif ($isMasterCommittee) {
            $projectsQuery->where('modality', ProjectModality::MASTER->value);
        } elseif ($isDoctorateCommittee) {
            $projectsQuery->where('modality', ProjectModality::DOCTORATE->value);
        }

        $totalProjects = $selection->projects()->count();
        $totalAssigned = (clone $projectsQuery)->count();

        $totalReviews = 0;
        $totalReviewed = 0;
        if ($isReviewer) {
            $totalReviews = ReviewAssignment::where('user_id', $user->id)
                ->whereHas('project', fn ($q) => $q->where('selection_process_id', $selection->id))
                ->count();

            $totalReviewed = Review::whereHas('reviewAssignment', function ($query) use ($user, $selection) {
                $query->where('user_id', $user->id)
                    ->whereHas('project', fn ($q) => $q->where('selection_process_id', $selection->id));
            })->where('status', ReviewStatus::SUBMITTED)->count();
        }

        $totalProjectReviews = $totalAssigned;
        $totalProjectReviewed = $totalReviewed;
        $globalStats = self::selectionProcessStats($selection);

        return [
            'total_projects' => $totalProjects,
            'total_assigned' => $totalAssigned,
            'total_reviewed' => $totalReviewed,
            'total_reviews' => $totalReviews,
            'total_project_reviewed' => $totalProjectReviewed,
            'total_project_reviews' => $totalProjectReviews,
            'written_exams' => $selection->projects()
                ->whereNotNull('review_score')
                ->whereNot('stage', ProjectStage::REJECTED)
                ->where('modality', ProjectModality::MASTER->value)
                ->count(),
            'written_examined' => $selection->projects()->has('writtenExam')->count(),
            'written_exam_passed' => $globalStats['written_exam_passed'],
            'written_exam_failed' => $globalStats['written_exam_failed'],
            'committee_evaluations' => $selection->projects()
                ->whereNotNull('review_score')
                ->whereNot('stage', ProjectStage::REJECTED)
                ->count(),
            'committee_evaluated' => $selection->projects()
                ->whereNotNull('committee_score')
                ->count(),
            'homologation_total' => $globalStats['homologation_total'],
            'homologation_revised' => $globalStats['homologation_revised'],
            'homologation_rejected' => $globalStats['homologation_rejected'],
            'homologation_accepted' => $globalStats['homologation_accepted'],
            'distribution_not_passed' => $globalStats['distribution_not_passed'],
            'review_not_passed' => $globalStats['review_not_passed'],
            'review_passed' => $globalStats['review_passed'],
            'written_exam_not_passed' => $globalStats['written_exam_not_passed'],
            'committee_not_passed' => $globalStats['committee_not_passed'],
            'final_results' => $globalStats['final_results'],
            'final_resulted' => $globalStats['final_resulted'],
        ];
    }

    /**
     * @return array{audience: 'management'|'reviewer'|'committee', modality: string|null, stats: array<string, int>}
     */
    public static function dashboardStats(SelectionProcess $selection, User $user): array
    {
        if ($user->can('projects.manage') || $user->hasRole(UserRoles::ADMIN->value)) {
            return [
                'audience' => 'management',
                'modality' => null,
                'stats' => self::selectionProcessStats($selection),
            ];
        }

        if ($user->hasRole(UserRoles::REVIEWER->value)) {
            return [
                'audience' => 'reviewer',
                'modality' => null,
                'stats' => self::selectionProcessStatsByReviewed($selection, $user),
            ];
        }

        if (! $user->hasAnyRole([
            UserRoles::MASTER_COMMITTEE->value,
            UserRoles::DOCTORATE_COMMITTEE->value,
        ])) {
            return [
                'audience' => 'management',
                'modality' => null,
                'stats' => self::selectionProcessStats($selection),
            ];
        }

        $modality = $user->hasRole(UserRoles::MASTER_COMMITTEE->value)
            ? ProjectModality::MASTER
            : ProjectModality::DOCTORATE;
        $projects = $selection->projects()->where('modality', $modality->value);
        $committeeProjects = (clone $projects)
            ->whereNotNull('review_score')
            ->whereNot('stage', ProjectStage::REJECTED);
        $evaluatedProjects = (clone $projects)->whereNotNull('committee_score');

        $stats = self::selectionProcessStats($selection);
        $stats['total_projects'] = (clone $projects)->count();
        $stats['total_assigned'] = $stats['total_projects'];
        $stats['committee_evaluations'] = $committeeProjects->count();
        $stats['committee_evaluated'] = $evaluatedProjects->count();
        $stats['committee_passed'] = (clone $projects)
            ->whereHas('committeeEvaluation', fn ($query) => $query->where('passed', true))
            ->count();
        $stats['committee_not_passed'] = (clone $projects)
            ->whereHas('committeeEvaluation', fn ($query) => $query->where('passed', false))
            ->count();

        return [
            'audience' => 'committee',
            'modality' => $modality->value,
            'stats' => $stats,
        ];
    }

    public static function selectionProcessPhases(): array
    {
        return collect(SelectionProcessPhases::cases())->map(fn ($phase) => [
            'name' => $phase->name,
            'value' => $phase->value,
            'label' => $phase->label(),
        ])->toArray();
    }

    public static function reviewScoreOptions(): array
    {
        return collect(ReviewScore::cases())->map(fn ($score) => [
            'name' => $score->name,
            'value' => $score->value,
            'label' => $score->label(),
            'description' => $score->description(),
        ])->toArray();
    }
}
