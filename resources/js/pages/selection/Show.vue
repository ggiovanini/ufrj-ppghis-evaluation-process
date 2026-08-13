<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { CheckCircle2, Download, MoreHorizontal } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import CommitteeList from '@/components/selection/CommitteeList.vue';
import HomologationProjectList from '@/components/selection/HomologationProjectList.vue';
import ProjectList from '@/components/selection/ProjectList.vue';
import ResultList from '@/components/selection/ResultList.vue';
import ReviewerList from '@/components/selection/ReviewerList.vue';
import SelectionStats from '@/components/selection/SelectionStats.vue';
import WrittenExamList from '@/components/selection/WrittenExamList.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { dashboard } from '@/routes';
import selectionRoutes from '@/routes/selection';
import routeProjects from '@/routes/selection/projects';
import type { DataFilters, DataPagination } from '@/types/pagination';
import type { Project } from '@/types/projects';
import type { Reviewer } from '@/types/reviewer';
import type {
    SelectionProcessStats,
    SelectionProcess,
    SelectionProcessPhaseObject,
} from '@/types/selection-process';

const props = defineProps<{
    selection: {
        data: SelectionProcess;
    };
    projects: DataPagination<Project>;
    reviewers: Reviewer[];
    filters?: DataFilters;
    stats: SelectionProcessStats;
    phases: SelectionProcessPhaseObject;
    homologationPendingProjects: number;
}>();

const canFinalizeResults = () =>
    props.stats.final_results > 0 &&
    props.stats.final_results === props.stats.final_resulted;

const downloadFinalReport = () => {
    window.location.href = selectionRoutes.projects.finalResult.report({
        selection: props.selection.data.id,
    }).url;
};

const finalizeResults = () => {
    if (!canFinalizeResults()) {
        return;
    }

    if (
        !window.confirm(
            'Tem certeza que deseja finalizar os resultados e avançar para a última etapa?',
        )
    ) {
        return;
    }

    router.post(selectionRoutes.finalize(props.selection.data.id).url);
};

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Painel',
            href: dashboard().url,
        },
        {
            title: props.selection.data.name,
            href: '#',
        },
    ],
});
</script>

<template>
    <Head :title="selection.data.name" />

    <SelectionStats
        :stats="stats"
        :phase="selection.data.phase"
        :selection="selection.data"
    />

    <template v-if="selection.data.phase === 'HOMOLOGATION'">
        <Heading
            title="Homologação dos cadastros"
            description="Confira os dados importados antes da distribuição"
            class="mt-6"
        />
        <HomologationProjectList
            :selection="selection.data"
            :projects="projects"
            :filters="filters"
            :pending-projects="homologationPendingProjects"
        />
    </template>

    <template v-if="selection.data.phase === 'DISTRIBUTION'">
        <Heading
            title="Lista de projetos"
            description="Verifique abaixo os projetos que precisam ser atríbuidos a avaliadores"
            class="mt-6"
        />
        <ProjectList
            :selection="selection.data"
            :projects="projects"
            :reviewers="reviewers"
            :filters="filters"
            :stats="stats"
        />
    </template>

    <template v-if="selection.data.phase === 'REVIEW'">
        <Heading
            title="Lista de avaliadores"
            description="Verifique abaixo como esta o desempenho dos avaliadores"
            class="mt-6"
        >
            <div class="flex flex-1 flex-row items-center justify-end gap-2">
                <Button variant="outline" as-child>
                    <Link
                        :href="
                            routeProjects.index({
                                selection: selection.data.id,
                            })
                        "
                        >Lista de projetos</Link
                    >
                </Button>
            </div>
        </Heading>
        <ReviewerList :selection="selection.data" :reviewers="reviewers" />
    </template>

    <template v-if="selection.data.phase === 'WRITTEN_EXAM'">
        <Heading
            title="Prova do mestrado"
            description="Esse é o momento que você vai informar as notas das provas do mestrado"
            class="mt-6"
        >
            <div class="flex flex-1 flex-row items-center justify-end gap-2">
                <Button variant="outline" as-child>
                    <Link
                        :href="
                            routeProjects.index({
                                selection: selection.data.id,
                            })
                        "
                        >Lista de projetos</Link
                    >
                </Button>
            </div>
        </Heading>
        <WrittenExamList
            :selection="selection.data"
            :projects="projects"
            :reviewers="reviewers"
            :filters="filters"
            :stats="stats"
        />
    </template>

    <template v-if="selection.data.phase === 'COMMITTEE'">
        <Heading
            title="Avaliação do comitê"
            description="Acompanhe como está a avaliação dos comitês"
            class="mt-6"
        >
            <div class="flex flex-1 flex-row items-center justify-end gap-2">
                <Button variant="outline" as-child>
                    <Link
                        :href="
                            routeProjects.index({
                                selection: selection.data.id,
                            })
                        "
                        >Lista de projetos</Link
                    >
                </Button>
            </div>
        </Heading>
        <CommitteeList
            :selection="selection.data"
            :projects="projects"
            :filters="filters"
            :stats="stats"
        />
    </template>

    <template v-if="selection.data.phase === 'RESULTS'">
        <Heading
            title="Exibição dos resultados"
            description="Essa é a prévis dos resultados antes da finalização."
            class="mt-6"
        >
            <div class="flex flex-1 flex-row items-center justify-end gap-2">
                <Button variant="outline" as-child>
                    <Link
                        :href="
                            routeProjects.index({
                                selection: selection.data.id,
                            })
                        "
                        >Lista de projetos</Link
                    >
                </Button>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="outline"
                            size="icon"
                            aria-label="Mais ações"
                        >
                            <MoreHorizontal class="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem @click="downloadFinalReport">
                            <Download class="mr-2 h-4 w-4" />
                            Baixar relatório final
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            :disabled="!canFinalizeResults()"
                            @click="finalizeResults"
                        >
                            <CheckCircle2 class="mr-2 h-4 w-4" />
                            Finalizar e avançar
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </Heading>
        <ResultList
            :selection="selection.data"
            :projects="projects"
            :filters="filters"
            :stats="stats"
        />
    </template>
</template>
