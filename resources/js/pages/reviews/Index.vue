<script setup lang="ts">
import { Head, setLayoutProps, usePage } from '@inertiajs/vue3';
import { ClipboardCheck } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import ReviewProjectList from '@/components/reviews/ReviewProjectList.vue';
import { dashboard } from '@/routes';
import { authCan } from '@/types';
import type { DataFilters, DataPagination, Resource } from '@/types/pagination';
import type { Project } from '@/types/projects';
import type {
    SelectionProcess,
    SelectionProcessPhaseObject,
    SelectionProcessStats,
} from '@/types/selection-process';

defineProps<{
    selection: Resource<SelectionProcess>;
    projects: DataPagination<Project>;
    phases: SelectionProcessPhaseObject[];
    stats: SelectionProcessStats;
    filters?: DataFilters;
}>();

const page = usePage();
const isCommittee = authCan(page.props.auth, 'committee.evaluate');

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Painel',
            href: dashboard().url,
        },
        {
            title: 'Avaliar',
            href: '#',
        },
    ],
});
</script>

<template>
    <Head :title="`${selection.data.name}: Avaliar`" />

    <Heading
        title="Avaliar projetos"
        :description="
            isCommittee
                ? 'Abaixo estão listados os projetos da modalidade da sua banca para avaliação'
                : 'Abaixo estão listados os projetos que foram atribuídos a você para avaliação'
        "
        :icon="ClipboardCheck"
        class="mt-6"
    />

    <ReviewProjectList
        :selection="selection.data"
        :projects="projects"
        :filters="filters"
        :mode="isCommittee ? 'committee' : 'review'"
    />
</template>
