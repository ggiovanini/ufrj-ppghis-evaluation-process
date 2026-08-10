<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { Download, MoreHorizontal } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import ProjectListOnlyShow from '@/components/selection/ProjectListOnlyShow.vue';
import SelectionStats from '@/components/selection/SelectionStats.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
        status?: string;
        modality?: 'master' | 'doctorate';
    };
    stats: SelectionProcessStats;
    phases: SelectionProcessPhaseObject[];
}>();

const downloadFinalReport = () => {
    window.location.href = selectionRoute.projects.finalResult.report({
        selection: props.selection.data.id,
    }).url;
};

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
    >
        <div class="flex flex-1 flex-row items-center justify-end gap-2">
            <DropdownMenu v-if="selection.data.phase === 'FINISHED'">
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
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </Heading>
    <ProjectListOnlyShow
        :selection="selection.data"
        :projects="projects"
        :reviewers="reviewers"
        :filters="filters"
        :stats="stats"
    />
</template>
