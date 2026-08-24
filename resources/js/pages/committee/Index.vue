<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { ClipboardCheck } from '@lucide/vue';
import CommitteeProjectList from '@/components/committee/CommitteeProjectList.vue';
import Heading from '@/components/Heading.vue';
import { dashboard } from '@/routes';
import type { DataFilters, DataPagination, Resource } from '@/types/pagination';
import type { Project } from '@/types/projects';
import type { SelectionProcess } from '@/types/selection-process';

defineProps<{
    selection: Resource<SelectionProcess>;
    projects: DataPagination<Project>;
    filters?: DataFilters;
}>();

setLayoutProps({ breadcrumbs: [{ title: 'Painel', href: dashboard().url }, { title: 'Provas orais', href: '#' }] });
</script>

<template>
    <Head :title="`${selection.data.name}: Provas orais`" />
    <Heading title="Provas orais" description="Avalie os projetos da modalidade da sua banca" :icon="ClipboardCheck" class="mt-6" />
    <CommitteeProjectList :selection="selection.data" :projects="projects" :filters="filters" />
</template>
