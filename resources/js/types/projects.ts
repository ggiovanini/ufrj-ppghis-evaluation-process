import type { ResourceCollection } from '@/types/pagination';
import type { ReviewAssignment } from '@/types/reviewer';

export type ProjectStage =
    | 'imported'
    | 'homologated'
    | 'review'
    | 'written_exam'
    | 'committee'
    | 'finished'
    | 'rejected';
export type ProjectStageLabel =
    | 'Em homologação'
    | 'Em distribuição'
    | 'Em avaliação'
    | 'Em aplicação de prova'
    | 'Em avaliação do comitê'
    | 'Aprovado'
    | 'Reprovado';

export type ProjectModality = 'master' | 'doctorate';

export type ProjectModalityLabel = 'Mestrado' | 'Doutorado';
export type ProjectHomologationStatus = 'pending' | 'approved' | 'rejected';

export interface Project {
    id: number;
    register_id: string;
    submitted_at: string | null;
    potential_duplicate: boolean;
    duplicate_group: string | null;
    duplicate_group_size: number | null;
    duplicate_match_reasons: string[];
    candidate_name: string;
    title: string;
    modality: ProjectModality;
    modality_label: ProjectModalityLabel;
    stage: ProjectStage;
    stage_label: ProjectStageLabel;
    rejected_on_stage: ProjectStage | null;
    rejected_on_stage_label: ProjectStageLabel | null;
    indication: string;
    has_indication: boolean;
    homologation_status: ProjectHomologationStatus;
    homologation_status_label: string;
    homologation_reason: string | null;
    review_assignments: ReviewAssignment[];
    is_evaluated: boolean;
    evaluated_percentil: number;
    review_score: number;
    review_score_label: string;
    written_exam_score: number;
    written_exam_score_label: string;
    written_exam_score_passes: boolean | null;
    committee_score: number;
    committee_score_label: string;
    committee_score_passes: boolean | null;
    final_score: number;
    final_score_label: string;
    final_score_passes: boolean | null;
    committee_evaluation?: {
        score?: number | null;
        passed?: boolean | null;
        comments?: string | null;
        submitted_at?: string | null;
    } | null;
}

export interface ProjectWithDetail {
    id: number;
    register_id: string;
    submitted_at: string | null;
    potential_duplicate: boolean;
    duplicate_group: string | null;
    duplicate_group_size: number | null;
    duplicate_match_reasons: string[];
    candidate_name: string;
    title: string;
    description: string;
    indication: string;
    has_indication: boolean;
    homologation_status: ProjectHomologationStatus;
    homologation_status_label: string;
    homologation_reason: string | null;
    modality: ProjectModality;
    modality_label: ProjectModalityLabel;
    stage: ProjectStage;
    stage_label: ProjectStageLabel;
    rejected_on_stage: ProjectStage | null;
    original_content: Record<string, any>;
    content: {
        content?: Array<{ label: string; value: unknown }>;
        documents?: Array<{
            label: string;
            name: string;
            filename: string;
            ext?: string;
            size?: number;
            path?: string;
            url?: string;
        }>;
    };
    review_assignments: ResourceCollection<ReviewAssignment>;
    written_exam: any | null;
    committee_evaluation: any | null;
    final_results: any | null;
    is_evaluated: boolean;
    evaluated_percentil: number;
    review_score: number;
    review_score_label: string;
    written_exam_score: number;
    written_exam_score_label: string;
    written_exam_score_passes: boolean | null;
    committee_score: number;
    committee_score_label: string;
    committee_score_passes: boolean | null;
    final_score: number;
    final_score_label: string;
    final_score_passes: boolean | null;
}
