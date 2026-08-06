<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type {
    SelectionProcessStats,
    SelectionProcessPhase,
} from '@/types/selection-process';

defineProps<{
    stats: SelectionProcessStats;
    phase: SelectionProcessPhase;
}>();
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
        <Card
            class="gap-2"
            :class="[
                phase === 'HOMOLOGATION'
                    ? 'outline-4 outline-foreground/40'
                    : '',
            ]"
        >
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0"
            >
                <CardTitle
                    class="text-sm text-muted-foreground uppercase"
                    :class="[
                        phase === 'HOMOLOGATION' ? 'font-bold' : 'font-medium',
                    ]"
                >
                    Homologação
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="text-4xl font-bold">
                    {{ stats.homologation_approved
                    }}<span class="text-2xl"
                        >/{{ stats.homologation_total }}</span
                    >
                </div>
                <div class="text-xs">Projetos aprovados</div>
                <div class="mt-1 h-2 w-full rounded-full bg-muted">
                    <div
                        v-if="stats.homologation_total > 0"
                        class="h-2 rounded-full bg-primary transition-all"
                        :style="{
                            width: `${(stats.homologation_approved / stats.homologation_total) * 100}%`,
                        }"
                    ></div>
                </div>
                <div class="mt-3 border-t pt-2 text-xs text-muted-foreground">
                    Não avançaram: {{ stats.homologation_rejected }}
                </div>
            </CardContent>
        </Card>
        <Card
            class="gap-2"
            :class="[
                ['IMPORT', 'DISTRIBUTION'].includes(phase)
                    ? 'outline-4 outline-foreground/40'
                    : '',
            ]"
            v-if="stats.total_projects"
        >
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0"
            >
                <CardTitle
                    class="text-sm text-muted-foreground uppercase"
                    :class="[
                        ['IMPORT', 'DISTRIBUTION'].includes(phase)
                            ? 'font-bold'
                            : 'font-medium',
                    ]"
                    >Distribuição</CardTitle
                >
            </CardHeader>
            <CardContent>
                <div class="flex flex-col">
                    <div class="text-4xl font-bold">
                        {{ stats.total_assigned
                        }}<span class="text-2xl"
                            >/{{ stats.total_projects }}</span
                        >
                    </div>
                    <div class="text-xs">Projetos completos</div>
                </div>
                <div class="mt-1 h-2 w-full rounded-full bg-muted">
                    <div
                        v-if="stats.total_projects > 0"
                        class="h-2 rounded-full bg-primary transition-all"
                        :style="{
                            width: `${(stats.total_assigned / stats.total_projects) * 100}%`,
                        }"
                    ></div>
                </div>
                <div class="mt-3 border-t pt-2 text-xs text-muted-foreground">
                    Não avançaram: {{ stats.distribution_not_passed }}
                </div>
            </CardContent>
        </Card>
        <Card
            class="gap-2"
            :class="[
                phase === 'REVIEW' ? 'outline-4 outline-foreground/40' : '',
            ]"
        >
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle
                    class="text-sm font-medium text-muted-foreground uppercase"
                    :class="[phase === 'REVIEW' ? 'font-bold' : 'font-medium']"
                    >Avaliações</CardTitle
                >
            </CardHeader>
            <CardContent>
                <div class="flex flex-row items-end justify-between">
                    <div class="flex flex-col">
                        <div class="text-4xl font-bold">
                            {{ stats.total_reviewed
                            }}<span class="text-2xl"
                                >/{{ stats.total_reviews }}</span
                            >
                        </div>
                        <div class="text-xs">Avaliações</div>
                    </div>
                    <div class="flex flex-col items-end">
                        <div class="text-2xl font-bold">
                            {{ stats.total_project_reviewed }}/{{
                                stats.total_project_reviews
                            }}
                        </div>
                        <div class="text-xs">Projetos</div>
                    </div>
                </div>
                <div class="mt-1 h-2 w-full rounded-full bg-muted">
                    <div
                        v-if="stats.total_project_reviews > 0"
                        class="h-2 rounded-full bg-primary transition-all"
                        :style="{
                            width: `${(stats.total_project_reviewed / stats.total_project_reviews) * 100}%`,
                        }"
                    ></div>
                </div>
                <div class="mt-3 border-t pt-2 text-xs text-muted-foreground">
                    Não avançaram: {{ stats.review_not_passed }}
                </div>
            </CardContent>
        </Card>
        <Card
            class="gap-2"
            :class="[
                phase === 'WRITTEN_EXAM'
                    ? 'outline-4 outline-foreground/40'
                    : '',
            ]"
        >
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle
                    class="text-sm font-medium text-muted-foreground uppercase"
                    :class="[
                        phase === 'WRITTEN_EXAM' ? 'font-bold' : 'font-medium',
                    ]"
                    >Provas Escritas</CardTitle
                >
            </CardHeader>
            <CardContent>
                <div class="text-4xl font-bold">
                    {{ stats.written_examined
                    }}<span class="text-2xl">/{{ stats.written_exams }}</span>
                </div>
                <div class="mt-1 h-2 w-full rounded-full bg-muted">
                    <div
                        v-if="stats.written_exams > 0"
                        class="h-2 rounded-full bg-primary transition-all"
                        :style="{
                            width: `${(stats.written_examined / stats.written_exams) * 100}%`,
                        }"
                    ></div>
                </div>
                <div class="mt-3 border-t pt-2 text-xs text-muted-foreground">
                    Não avançaram: {{ stats.written_exam_not_passed }}
                </div>
            </CardContent>
        </Card>
        <Card
            class="gap-2"
            :class="[
                phase === 'COMMITTEE' ? 'outline-4 outline-foreground/40' : '',
            ]"
        >
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle
                    class="text-sm font-medium text-muted-foreground uppercase"
                    :class="[
                        phase === 'COMMITTEE' ? 'font-bold' : 'font-medium',
                    ]"
                    >Avaliações de Comitê</CardTitle
                >
            </CardHeader>
            <CardContent>
                <div class="text-4xl font-bold">
                    {{ stats.committee_evaluated
                    }}<span class="text-2xl"
                        >/{{ stats.committee_evaluations }}</span
                    >
                </div>
                <div class="mt-1 h-2 w-full rounded-full bg-muted">
                    <div
                        v-if="stats.committee_evaluations > 0"
                        class="h-2 rounded-full bg-primary transition-all"
                        :style="{
                            width: `${(stats.committee_evaluated / stats.committee_evaluations) * 100}%`,
                        }"
                    ></div>
                </div>
                <div class="mt-3 border-t pt-2 text-xs text-muted-foreground">
                    Não avançaram: {{ stats.committee_not_passed }}
                </div>
            </CardContent>
        </Card>
    </div>
</template>
