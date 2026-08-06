<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Search,
    X,
    ArrowUp,
    ArrowDown,
    ArrowUpDown,
    Asterisk,
    MoreHorizontal,
    Eye,
    CheckCircleIcon,
} from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { ref } from 'vue';
import Pagination from '@/components/Pagination.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import selectionRoutes from '@/routes/selection';
import type { DataFilters, DataPagination } from '@/types/pagination';
import type { Project } from '@/types/projects';
import type { Reviewer } from '@/types/reviewer';
import type {
    SelectionProcessStats,
    SelectionProcess,
} from '@/types/selection-process';

const props = defineProps<{
    selection: SelectionProcess;
    projects: DataPagination<Project>;
    reviewers: Reviewer[];
    filters?: DataFilters;
    stats: SelectionProcessStats;
}>();

const search = ref(props.filters?.search || '');

watchDebounced(
    search,
    (value) => {
        router.get(
            window.location.pathname,
            {
                ...props.filters,
                search: value,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    },
    { debounce: 300 },
);

const sortBy = (column: string) => {
    let direction: 'asc' | 'desc' = 'asc';

    if (props.filters?.sort === column && props.filters?.direction === 'asc') {
        direction = 'desc';
    }

    router.get(
        window.location.pathname,
        {
            ...props.filters,
            sort: column,
            direction: direction,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const clearSearch = () => {
    search.value = '';
};

const navigateToShowProject = (projectId: number) => {
    router.visit(
        selectionRoutes.projects.show({
            selection: props.selection.id,
            project: projectId,
        }).url,
    );
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div
            class="relative flex w-full flex-col overflow-hidden rounded-xl border p-2"
        >
            <PlaceholderPattern class="z-0" />
            <div class="z-10 mb-2 flex items-center justify-between gap-4 p-2">
                <div class="relative w-full max-w-sm">
                    <Search
                        class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        placeholder="Filtrar projetos..."
                        class="pl-10"
                    />
                    <button
                        v-if="search"
                        @click="clearSearch"
                        class="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <div
                class="z-10 flex w-full overflow-hidden rounded-md border bg-card outline-2 outline-foreground/10"
            >
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead
                                class="cursor-pointer hover:bg-muted/50"
                                @click="sortBy('candidate_name')"
                            >
                                <div class="flex items-center gap-2 ps-2">
                                    Candidato
                                    <template
                                        v-if="
                                            filters?.sort === 'candidate_name'
                                        "
                                    >
                                        <ArrowUp
                                            v-if="filters?.direction === 'asc'"
                                            class="h-4 w-4"
                                        />
                                        <ArrowDown v-else class="h-4 w-4" />
                                    </template>
                                    <ArrowUpDown
                                        v-else
                                        class="h-4 w-4 text-muted-foreground/50"
                                    />
                                </div>
                            </TableHead>
                            <TableHead class="text-center">Nota</TableHead>
                            <TableHead class="text-center">Progresso</TableHead>
                            <TableHead class="text-center">Status</TableHead>
                            <TableHead>Avaliadores</TableHead>
                            <TableHead
                                class="flex items-center justify-end pe-4"
                            >
                                <Asterisk class="h-4 w-4" />
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="project in projects.data"
                            :key="project.id"
                        >
                            <TableCell
                                class="ps-4 font-medium"
                                @click="navigateToShowProject(project.id)"
                            >
                                <div class="flex flex-col gap-1">
                                    <span>{{ project.candidate_name }}</span>
                                    <span
                                        class="line-clamp-1 text-xs font-normal text-muted-foreground"
                                        >{{ project.title }}</span
                                    >
                                    <Badge variant="secondary">
                                        {{ project.modality_label }}
                                    </Badge>
                                </div>
                            </TableCell>
                            <TableCell class="text-center">
                                <Badge
                                    v-if="project.review_score"
                                    variant="secondary"
                                >
                                    {{ project.review_score_label }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-center">
                                <div
                                    class="flex items-center justify-center gap-2"
                                >
                                    <div
                                        class="h-2 w-24 overflow-hidden rounded-full bg-muted"
                                    >
                                        <div
                                            class="h-full bg-primary transition-all"
                                            :style="{
                                                width: `${project.evaluated_percentil}%`,
                                            }"
                                        />
                                    </div>
                                    <span class="text-xs tabular-nums">
                                        {{ project.evaluated_percentil }}%
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell class="pe-4 text-center">
                                <Badge :class="project.stage === 'rejected' ? 'bg-orange-300' : ''">
                                    {{ project.stage_label }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-wrap gap-2">
                                    <Badge
                                        v-for="assignment in project.review_assignments"
                                        :key="assignment.id"
                                        :variant="
                                            assignment.chosen_by_candidate
                                                ? 'default'
                                                : 'secondary'
                                        "
                                        class="border-solid"
                                        :class="
                                            assignment.review?.status ===
                                            'submitted'
                                                ? assignment.chosen_by_candidate
                                                    ? 'bg-green-500'
                                                    : 'bg-green-500/20'
                                                : assignment.review?.status ===
                                                    'pendent'
                                                  ? 'opacity-50'
                                                  : ''
                                        "
                                    >
                                        {{ assignment.user.name }}
                                        <CheckCircleIcon
                                            class="h-4 w-4"
                                            v-if="
                                                assignment.review?.status ===
                                                'submitted'
                                            "
                                        />
                                    </Badge>
                                </div>
                            </TableCell>
                            <TableCell class="text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button
                                            variant="ghost"
                                            class="h-8 w-8 p-0"
                                        >
                                            <span class="sr-only"
                                                >Abrir menu</span
                                            >
                                            <MoreHorizontal class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem
                                            @click="
                                                navigateToShowProject(
                                                    project.id,
                                                )
                                            "
                                        >
                                            <Eye class="mr-1 h-4 w-4" /> Ver
                                            Projeto
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="projects.data.length === 0">
                            <TableCell colspan="4" class="h-24 text-center">
                                Nenhum projeto encontrado.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            <Pagination :meta="projects.meta" class="z-10" />
        </div>
    </div>
</template>
