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
        $writtenExams = (clone $approvedProjects)
            ->whereNotNull('review_score')
            ->whereNot('stage', ProjectStage::REJECTED)
            ->where('modality', ProjectModality::MASTER->value);
        $committeeEvaluations = (clone $approvedProjects)
            ->whereNotNull('review_score')
            ->whereNot('stage', ProjectStage::REJECTED);
        $writtenExamsTotal = $writtenExams->count();
        $writtenExamsCompleted = (clone $writtenExams)->has('writtenExam')->count();
        $committeeEvaluationsTotal = $committeeEvaluations->count();
        $committeeEvaluationsCompleted = (clone $committeeEvaluations)->has('committeeEvaluation')->count();

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
            'committee_evaluations' => $committeeEvaluationsTotal,
            'committee_evaluated' => $committeeEvaluationsCompleted,
            'homologation_total' => $homologationTotal,
            'homologation_approved' => $homologationApproved,
            'homologation_rejected' => $homologationRejected,
            'distribution_not_passed' => max(0, $homologationApproved - $totalAssigned),
            'review_not_passed' => max(0, $homologationApproved - $totalProjectReviewed),
            'written_exam_not_passed' => max(0, $writtenExamsTotal - $writtenExamsCompleted),
            'committee_not_passed' => max(0, $committeeEvaluationsTotal - $committeeEvaluationsCompleted),
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
            'committee_evaluations' => $selection->projects()
                ->whereNotNull('review_score')
                ->whereNot('stage', ProjectStage::REJECTED)
                ->count(),
            'committee_evaluated' => $selection->projects()->has('committeeEvaluation')->count(),
            'homologation_total' => $globalStats['homologation_total'],
            'homologation_approved' => $globalStats['homologation_approved'],
            'homologation_rejected' => $globalStats['homologation_rejected'],
            'distribution_not_passed' => $globalStats['distribution_not_passed'],
            'review_not_passed' => $globalStats['review_not_passed'],
            'written_exam_not_passed' => $globalStats['written_exam_not_passed'],
            'committee_not_passed' => $globalStats['committee_not_passed'],
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
