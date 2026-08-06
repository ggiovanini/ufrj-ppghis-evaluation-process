export interface SelectionProcessStats {
    total_projects: number;
    total_assigned: number;
    total_reviewed: number;
    total_reviews: number;
    total_project_reviewed: number;
    total_project_reviews: number;
    written_examined: number;
    written_exams: number;
    committee_evaluated: number;
    committee_evaluations: number;
    homologation_total: number;
    homologation_approved: number;
    homologation_rejected: number;
    distribution_not_passed: number;
    review_not_passed: number;
    written_exam_not_passed: number;
    committee_not_passed: number;
}

export interface SelectionProcessPhaseObject {
    name: string;
    value: SelectionProcessPhase;
    label: string;
}

export type SelectionProcessPhase =
    | 'IMPORT'
    | 'HOMOLOGATION'
    | 'DISTRIBUTION'
    | 'REVIEW'
    | 'WRITTEN_EXAM'
    | 'COMMITTEE'
    | 'RESULTS'
    | 'FINISHED';
export type SelectionProcessPhaseLabel =
    | 'Importação'
    | 'Homologação'
    | 'Distribuição'
    | 'Revisão'
    | 'Prova escrita'
    | 'Comitê'
    | 'Resultados'
    | 'Finalizado';

export interface SelectionProcess {
    id: number;
    name: string;
    description: string;
    year: number;
    phase: SelectionProcessPhase;
    phase_label: SelectionProcessPhaseLabel;
}
