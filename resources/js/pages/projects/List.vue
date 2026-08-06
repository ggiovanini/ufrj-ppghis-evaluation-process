<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import ProjectList from '@/components/selection/ProjectList.vue';
import SelectionStats from '@/components/selection/SelectionStats.vue';
import { dashboard } from '@/routes';
import selectionRoute from '@/routes/selection';
import type { DataPagination } from '@/types/pagination';
import type { Project } from '@/types/projects';
import type { Reviewer } from '@/types/reviewer';
import type {
    SelectionProcess,
    SelectionProcessPhaseObject,
    SelectionProcessStats,
} from '@/types/selection-process';
import ProjectListOnlyShow from '@/components/selection/ProjectListOnlyShow.vue';

const props = defineProps<{
    selection: {
        data: SelectionProcess;
    };
    projects: DataPagination<Project>;
    reviewers: Reviewer[];
    filters?: {
        search?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
    };
    stats: SelectionProcessStats;
    phases: SelectionProcessPhaseObject[];
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Painel',
            href: dashboard().url,
        },
        {
            title: props.selection.data.name,
            href: selectionRoute.show(props.selection.data.id).url,
        },
        {
            title: 'Projetos',
            href: '#',
        },
    ],
});
</script>

<template>
    <Head :title="`${selection.data.name}: Projetos`" />

    <SelectionStats :stats="stats" :phase="selection.data.phase" />

    <Heading
        title="Lista de projetos"
        description="Verifique abaixo os projetos para acompanhar seu progresso"
        class="mt-6"
    />
    <ProjectListOnlyShow
        :selection="selection.data"
        :projects="projects"
        :reviewers="reviewers"
        :filters="filters"
        :stats="stats"
    />
</template>
