<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    CheckCircle2,
    ClipboardCheck,
    Clock3,
    FileText,
    Layers3,
} from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
} from '@/components/ui/card';
import { dashboard as dashboardRoute } from '@/routes';
import selectionRoutes from '@/routes/selection';
import type {
    DashboardData,
    SelectionProcess,
    SelectionProcessStats,
} from '@/types/selection-process';

const props = defineProps<{
    selection: { data: SelectionProcess };
    dashboard: DashboardData;
}>();

const stats = computed<SelectionProcessStats>(() => props.dashboard.stats);
const pendingHomologation = computed(() =>
    Math.max(
        0,
        stats.value.homologation_total - stats.value.homologation_revised,
    ),
);
const pendingReviews = computed(() =>
    Math.max(0, stats.value.total_reviews - stats.value.total_reviewed),
);
const pendingCommittee = computed(() =>
    Math.max(
        0,
        stats.value.committee_evaluations - stats.value.committee_evaluated,
    ),
);
const modalityLabel = computed(() =>
    props.dashboard.modality === 'master' ? 'Mestrado' : 'Doutorado',
);

const completion = (completed: number, total: number): number =>
    total > 0 ? Math.round((completed / total) * 100) : 0;

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Painel',
                href: dashboardRoute(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Painel" />

    <div class="flex flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
        <div
            class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between"
        >
            <div>
                <p class="text-sm text-muted-foreground">
                    Processo seletivo {{ selection.data.year }}
                </p>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ selection.data.name }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    Etapa atual: {{ selection.data.phase_label }}
                </p>
            </div>
            <Button variant="outline" as-child>
                <Link :href="selectionRoutes.show(selection.data.id).url">
                    Acessar processo
                    <ArrowRight class="ml-2 h-4 w-4" />
                </Link>
            </Button>
        </div>

        <div
            v-if="dashboard.audience === 'management'"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
        >
            <Card>
                <CardHeader
                    ><CardDescription>Total de cadastros</CardDescription
                    ><Layers3 class="h-5 w-5 text-muted-foreground"
                /></CardHeader>
                <CardContent
                    ><div class="text-4xl font-bold">
                        {{ stats.total_projects }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Projetos no processo
                    </p></CardContent
                >
            </Card>
            <Card>
                <CardHeader
                    ><CardDescription>Homologação</CardDescription
                    ><ClipboardCheck class="h-5 w-5 text-muted-foreground"
                /></CardHeader>
                <CardContent
                    ><div class="text-4xl font-bold">
                        {{ stats.homologation_revised }}/{{
                            stats.homologation_total
                        }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        {{ pendingHomologation }} pendentes
                    </p></CardContent
                >
            </Card>
            <Card>
                <CardHeader
                    ><CardDescription>Avaliações</CardDescription
                    ><FileText class="h-5 w-5 text-muted-foreground"
                /></CardHeader>
                <CardContent
                    ><div class="text-4xl font-bold">
                        {{ stats.total_reviewed }}/{{ stats.total_reviews }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        {{ pendingReviews }} pendentes
                    </p></CardContent
                >
            </Card>
            <Card>
                <CardHeader
                    ><CardDescription>Resultados</CardDescription
                    ><CheckCircle2 class="h-5 w-5 text-muted-foreground"
                /></CardHeader>
                <CardContent
                    ><div class="text-4xl font-bold">
                        {{ stats.final_resulted }}/{{ stats.final_results }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Resultados calculados
                    </p></CardContent
                >
            </Card>
        </div>

        <div
            v-else-if="dashboard.audience === 'reviewer'"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
        >
            <Card
                ><CardHeader
                    ><CardDescription>Projetos atribuídos</CardDescription
                    ><Layers3
                        class="h-5 w-5 text-muted-foreground" /></CardHeader
                ><CardContent
                    ><div class="text-4xl font-bold">
                        {{ stats.total_assigned }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Na sua fila de avaliação
                    </p></CardContent
                ></Card
            >
            <Card
                ><CardHeader
                    ><CardDescription>Avaliações enviadas</CardDescription
                    ><CheckCircle2
                        class="h-5 w-5 text-muted-foreground" /></CardHeader
                ><CardContent
                    ><div class="text-4xl font-bold">
                        {{ stats.total_reviewed }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        de {{ stats.total_reviews }} atribuídas
                    </p></CardContent
                ></Card
            >
            <Card
                ><CardHeader
                    ><CardDescription>Pendências</CardDescription
                    ><Clock3
                        class="h-5 w-5 text-muted-foreground" /></CardHeader
                ><CardContent
                    ><div class="text-4xl font-bold">{{ pendingReviews }}</div>
                    <p class="text-xs text-muted-foreground">
                        Avaliações restantes
                    </p></CardContent
                ></Card
            >
            <Card
                ><CardHeader
                    ><CardDescription>Conclusão</CardDescription
                    ><ClipboardCheck
                        class="h-5 w-5 text-muted-foreground" /></CardHeader
                ><CardContent
                    ><div class="text-4xl font-bold">
                        {{
                            completion(
                                stats.total_reviewed,
                                stats.total_reviews,
                            )
                        }}%
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Do seu trabalho
                    </p></CardContent
                ></Card
            >
        </div>

        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card
                ><CardHeader
                    ><CardDescription>{{ modalityLabel }}</CardDescription
                    ><Layers3
                        class="h-5 w-5 text-muted-foreground" /></CardHeader
                ><CardContent
                    ><div class="text-4xl font-bold">
                        {{ stats.total_projects }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Projetos da comissão
                    </p></CardContent
                ></Card
            >
            <Card
                ><CardHeader
                    ><CardDescription>Avaliações concluídas</CardDescription
                    ><CheckCircle2
                        class="h-5 w-5 text-muted-foreground" /></CardHeader
                ><CardContent
                    ><div class="text-4xl font-bold">
                        {{ stats.committee_evaluated }}/{{
                            stats.committee_evaluations
                        }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        {{ pendingCommittee }} pendentes
                    </p></CardContent
                ></Card
            >
            <Card
                ><CardHeader
                    ><CardDescription>Aprovados</CardDescription
                    ><ClipboardCheck
                        class="h-5 w-5 text-muted-foreground" /></CardHeader
                ><CardContent
                    ><div class="text-4xl font-bold">
                        {{ stats.committee_passed }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Resultado da comissão
                    </p></CardContent
                ></Card
            >
            <Card
                ><CardHeader
                    ><CardDescription>Não aprovados</CardDescription
                    ><Clock3
                        class="h-5 w-5 text-muted-foreground" /></CardHeader
                ><CardContent
                    ><div class="text-4xl font-bold">
                        {{ stats.committee_not_passed }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Resultado da comissão
                    </p></CardContent
                ></Card
            >
        </div>
    </div>
</template>
