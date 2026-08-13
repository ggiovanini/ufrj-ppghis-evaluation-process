<script setup lang="ts">
import {
    ArrowDownToDot,
    ArrowRightToLineIcon,
    DownloadIcon,
} from '@lucide/vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import selectionRoutes from '@/routes/selection';
import type {
    SelectionProcessStats,
    SelectionProcessPhase,
    SelectionProcess,
} from '@/types/selection-process';

const downloadReportHomologation = () => {
    if (!props.selection) {
        return;
    }

    window.location.href = selectionRoutes.projects.homologation.report({
        selection: props.selection.id,
    }).url;
};

const downloadReportDistribution = () => {
    if (!props.selection) {
        return;
    }

    window.location.href = selectionRoutes.projects.distribution.report({
        selection: props.selection.id,
    }).url;
};

const downloadReportReview = () => {
    if (!props.selection) {
        return;
    }

    window.location.href = selectionRoutes.projects.review.report({
        selection: props.selection.id,
    }).url;
};

const downloadReportWrittenExam = () => {
    if (!props.selection) {
        return;
    }

    window.location.href = selectionRoutes.projects.writtenExam.report({
        selection: props.selection.id,
    }).url;
};

const downloadReportCommittee = () => {
    if (!props.selection) {
        return;
    }

    window.location.href = selectionRoutes.projects.committee.report({
        selection: props.selection.id,
    }).url;
};

const props = defineProps<{
    stats: SelectionProcessStats;
    phase: SelectionProcessPhase;
    selection?: SelectionProcess;
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
            v-if="
                [
                    'HOMOLOGATION',
                    'DISTRIBUTION',
                    'REVIEW',
                    'WRITTEN_EXAM',
                    'COMMITTEE',
                    'RESULTS',
                    'FINISHED',
                ].includes(phase)
            "
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
                <div class="mt-1 text-4xl font-bold">
                    {{ stats.homologation_revised
                    }}<span class="text-2xl"
                        >/{{ stats.homologation_total }}</span
                    >
                </div>
                <div class="text-xs">Projetos revisados</div>
                <div class="mt-1 h-2 w-full rounded-full bg-muted">
                    <div
                        v-if="stats.homologation_total > 0"
                        class="h-2 rounded-full bg-primary transition-all"
                        :style="{
                            width: `${(stats.homologation_revised / stats.homologation_total) * 100}%`,
                        }"
                    ></div>
                </div>
                <div
                    class="flex w-full flex-row items-end justify-between pt-6"
                >
                    <div class="flex flex-row">
                        <Badge
                            v-if="stats.homologation_rejected > 0"
                            variant="secondary"
                            :class="
                                stats.homologation_accepted
                                    ? 'rounded-r-none'
                                    : ''
                            "
                        >
                            <ArrowDownToDot class="h-2 w-2" />
                            {{ stats.homologation_rejected }}
                        </Badge>
                        <Badge
                            variant="default"
                            class="rounded-l-none bg-green-700 text-foreground"
                            v-if="stats.homologation_accepted > 0"
                        >
                            <ArrowRightToLineIcon class="h-2 w-2" />
                            {{ stats.homologation_accepted }}
                        </Badge>
                    </div>
                    <Button
                        variant="ghost"
                        class="-mr-2 -mb-2"
                        @click="downloadReportHomologation"
                        v-if="selection"
                    >
                        <DownloadIcon class="h-2 w-2" />
                    </Button>
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
            v-if="
                [
                    'DISTRIBUTION',
                    'REVIEW',
                    'WRITTEN_EXAM',
                    'COMMITTEE',
                    'RESULTS',
                    'FINISHED',
                ].includes(phase)
            "
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
                <div class="mt-1 flex flex-col">
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
                <div
                    class="flex w-full flex-row items-end justify-between pt-6"
                >
                    <div class="flex flex-row"></div>
                    <Button
                        variant="ghost"
                        class="-mr-2 -mb-2"
                        @click="downloadReportDistribution"
                        v-if="selection"
                    >
                        <DownloadIcon class="h-2 w-2" />
                    </Button>
                </div>
            </CardContent>
        </Card>
        <Card v-else class="relative">
            <PlaceholderPattern />
        </Card>
        <Card
            class="gap-2"
            :class="[
                phase === 'REVIEW' ? 'outline-4 outline-foreground/40' : '',
            ]"
            v-if="
                [
                    'REVIEW',
                    'WRITTEN_EXAM',
                    'COMMITTEE',
                    'RESULTS',
                    'FINISHED',
                ].includes(phase)
            "
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
                        <div class="flex flex-col items-end gap-0">
                            <div
                                class="border-b-2 border-b-foreground text-2xl leading-none font-bold"
                            >
                                {{ stats.total_project_reviewed }}
                            </div>
                            <div class="text-1xl leading-none">
                                {{ stats.total_project_reviews }}
                            </div>
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
                <div
                    class="flex w-full flex-row items-end justify-between pt-6"
                >
                    <div class="flex flex-row">
                        <Badge
                            v-if="stats.review_not_passed > 0"
                            variant="secondary"
                            :class="
                                stats.review_passed > 0 ? 'rounded-r-none' : ''
                            "
                        >
                            <ArrowDownToDot class="h-2 w-2" />
                            {{ stats.review_not_passed }}
                        </Badge>
                        <Badge
                            variant="default"
                            class="rounded-l-none bg-green-700 text-foreground"
                            v-if="stats.review_passed > 0"
                        >
                            <ArrowRightToLineIcon class="h-2 w-2" />
                            {{ stats.review_passed }}
                        </Badge>
                    </div>
                    <Button
                        variant="ghost"
                        class="-mr-2 -mb-2"
                        @click="downloadReportReview"
                        v-if="selection"
                    >
                        <DownloadIcon class="h-2 w-2" />
                    </Button>
                </div>
            </CardContent>
        </Card>
        <Card v-else class="relative">
            <PlaceholderPattern />
        </Card>
        <Card
            class="gap-2"
            :class="[
                phase === 'WRITTEN_EXAM'
                    ? 'outline-4 outline-foreground/40'
                    : '',
            ]"
            v-if="
                ['WRITTEN_EXAM', 'COMMITTEE', 'RESULTS', 'FINISHED'].includes(
                    phase,
                )
            "
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
                <div class="text-xs">Projetos do mestrado</div>
                <div class="mt-1 h-2 w-full rounded-full bg-muted">
                    <div
                        v-if="stats.written_exams > 0"
                        class="h-2 rounded-full bg-primary transition-all"
                        :style="{
                            width: `${(stats.written_examined / stats.written_exams) * 100}%`,
                        }"
                    ></div>
                </div>
                <div
                    class="flex w-full flex-row items-end justify-between pt-6"
                >
                    <div class="flex flex-row">
                        <Badge
                            v-if="stats.written_exam_failed > 0"
                            variant="secondary"
                            :class="
                                stats.written_exam_passed > 0
                                    ? 'rounded-r-none'
                                    : ''
                            "
                        >
                            <ArrowDownToDot class="h-2 w-2" />
                            {{ stats.written_exam_failed }}
                        </Badge>
                        <Badge
                            variant="default"
                            class="rounded-l-none bg-green-700 text-foreground"
                            v-if="stats.written_exam_passed > 0"
                        >
                            <ArrowRightToLineIcon class="h-2 w-2" />
                            {{ stats.written_exam_passed }}
                        </Badge>
                    </div>
                    <Button
                        variant="ghost"
                        class="-mr-2 -mb-2"
                        @click="downloadReportWrittenExam"
                        v-if="selection"
                    >
                        <DownloadIcon class="h-2 w-2" />
                    </Button>
                </div>
            </CardContent>
        </Card>
        <Card v-else class="relative">
            <PlaceholderPattern />
        </Card>
        <Card
            class="gap-2"
            :class="[
                phase === 'COMMITTEE' ? 'outline-4 outline-foreground/40' : '',
            ]"
            v-if="['COMMITTEE', 'RESULTS', 'FINISHED'].includes(phase)"
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
                <div class="text-xs">Projetos</div>
                <div class="mt-1 h-2 w-full rounded-full bg-muted">
                    <div
                        v-if="stats.committee_evaluations > 0"
                        class="h-2 rounded-full bg-primary transition-all"
                        :style="{
                            width: `${(stats.committee_evaluated / stats.committee_evaluations) * 100}%`,
                        }"
                    ></div>
                </div>
                <div
                    class="flex w-full flex-row items-end justify-between pt-6"
                >
                    <div class="flex flex-row">
                        <Badge
                            v-if="stats.committee_not_passed > 0"
                            variant="secondary"
                            :class="
                                stats.committee_passed > 0
                                    ? 'rounded-r-none'
                                    : ''
                            "
                        >
                            <ArrowDownToDot class="h-2 w-2" />
                            {{ stats.committee_not_passed }}
                        </Badge>
                        <Badge
                            variant="default"
                            class="rounded-l-none bg-green-700 text-foreground"
                            v-if="stats.committee_passed > 0"
                        >
                            <ArrowRightToLineIcon class="h-2 w-2" />
                            {{ stats.committee_passed }}
                        </Badge>
                    </div>
                    <Button
                        variant="ghost"
                        class="-mr-2 -mb-2"
                        @click="downloadReportCommittee"
                        v-if="selection"
                    >
                        <DownloadIcon class="h-2 w-2" />
                    </Button>
                </div>
            </CardContent>
        </Card>
        <Card v-else class="relative">
            <PlaceholderPattern />
        </Card>
    </div>
</template>
