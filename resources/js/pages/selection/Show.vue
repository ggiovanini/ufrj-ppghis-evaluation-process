<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import HomologationProjectList from '@/components/selection/HomologationProjectList.vue';
import ProjectList from '@/components/selection/ProjectList.vue';
import ReviewerList from '@/components/selection/ReviewerList.vue';
import SelectionStats from '@/components/selection/SelectionStats.vue';
import WrittenExamList from '@/components/selection/WrittenExamList.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
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

    <SelectionStats :stats="stats" :phase="selection.data.phase" />

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

    <template
        v-if="
            selection.data.phase === 'DISTRIBUTION' ||
            selection.data.phase === 'IMPORT'
        "
    >
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
</template>
