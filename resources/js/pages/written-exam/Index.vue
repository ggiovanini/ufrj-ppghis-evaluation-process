<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { ClipboardCheck } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import WrittenExamCommitteeList from '@/components/selection/WrittenExamCommitteeList.vue';
import { dashboard } from '@/routes';
import type { DataFilters, DataPagination, Resource } from '@/types/pagination';
import type { Project } from '@/types/projects';
import type { SelectionProcess, SelectionProcessPhaseObject } from '@/types/selection-process';

defineProps<{
    selection: Resource<SelectionProcess>;
    projects: DataPagination<Project>;
    phases: SelectionProcessPhaseObject[];
    filters?: DataFilters;
}>();

setLayoutProps({ breadcrumbs: [{ title: 'Painel', href: dashboard().url }, { title: 'Provas escritas', href: '#' }] });
</script>

<template>
    <Head :title="`${selection.data.name}: Provas escritas`" />
    <Heading title="Provas escritas" description="Informe as notas das provas escritas dos candidatos de mestrado" :icon="ClipboardCheck" class="mt-6" />
    <WrittenExamCommitteeList :selection="selection.data" :projects="projects" :filters="filters" />
</template>
