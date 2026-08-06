export interface Reviewer {
    id: number;
    name: string;
    email: string;
    roles?: { name: string; label: string }[];
    assigned_count?: number;
    completed_count?: number;
}

export type ReviewStatus = 'pendent' | 'draft' | 'submitted';
export type ReviewStatusLabel = 'Aguardando' | 'Rascunho' | 'Enviado';

export type ReviewScore = 0 | 1 | 2 | 3 | 4;
export type ReviewScoreLabel =
    | 'Aprovado'
    | 'Aprovado com ressalvas'
    | 'Indicação para reprovação'
    | 'Reprovado'
    | 'Não avaliado';

export type ReviewScoreOptions = {
    value: ReviewScore;
    label: string;
    description: string;
}[];

export interface Review {
    id: number;
    status: ReviewStatus;
    status_label: ReviewStatusLabel;
    score: ReviewScore;
    score_label: string;
    score_description: string;
    answers: Record<string, any>;
    comments: string;
    questions: string;
    submitted_at: string;
    pdf_url?: string | null;
    form?: {
        id: number | null;
        schema: { fields?: unknown[] } | null;
    };
}

export interface ReviewAssignment {
    id: number;
    user_id: number;
    user: Reviewer;
    review?: Review;
    chosen_by_candidate: boolean;
}
