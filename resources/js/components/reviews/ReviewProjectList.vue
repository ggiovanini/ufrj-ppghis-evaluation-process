<script setup lang="ts">
import { router, Link } from '@inertiajs/vue3';
import {
    Eye,
    Search,
    X,
    ArrowUp,
    ArrowDown,
    ArrowUpDown,
} from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { ref } from 'vue';
import Pagination from '@/components/Pagination.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import type { SelectionProcess } from '@/types/selection-process';

const props = defineProps<{
    selection: SelectionProcess;
    projects: DataPagination<Project>;
    filters?: DataFilters;
    mode?: 'review' | 'committee';
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
                page: 1,
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
                            <TableHead
                                class="cursor-pointer hover:bg-muted/50"
                                @click="sortBy('title')"
                            >
                                <div class="flex items-center gap-2">
                                    Título do Projeto
                                    <template v-if="filters?.sort === 'title'">
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
                            <TableHead>Modalidade</TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-muted/50"
                                @click="sortBy('status')"
                            >
                                <div class="flex items-center gap-2">
                                    Status
                                    <template v-if="filters?.sort === 'status'">
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
                            <TableHead class="pe-4 text-right">Ações</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="project in projects.data"
                            :key="project.id"
                        >
                            <TableCell class="ps-4 font-medium">
                                {{ project.candidate_name }}
                            </TableCell>
                            <TableCell>{{ project.title }}</TableCell>
                            <TableCell>
                                <Badge
                                    class="flex items-center gap-2 text-sm"
                                    variant="secondary"
                                >
                                    {{ project.modality_label }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <template v-if="mode === 'committee'">
                                    <Badge
                                        :class="
                                            project.committee_score !== null
                                                ? 'bg-green-500'
                                                : 'bg-muted-foreground'
                                        "
                                    >
                                        {{
                                            project.committee_score !== null
                                                ? 'Nota registrada'
                                                : 'Pendente'
                                        }}
                                    </Badge>
                                </template>
                                <template
                                    v-else
                                    v-for="assignment in project.review_assignments"
                                    :key="assignment.id"
                                >
                                    <Badge
                                        class="flex items-center gap-2"
                                        :class="{
                                            'bg-muted-foreground opacity-50':
                                                !assignment?.review ||
                                                assignment?.review?.status ===
                                                    'pendent',
                                            'bg-muted-foreground':
                                                !assignment?.review ||
                                                assignment?.review?.status ===
                                                    'draft',
                                            'bg-green-500':
                                                !assignment?.review ||
                                                assignment?.review?.status ===
                                                    'submitted',
                                        }"
                                    >
                                        {{ assignment.review?.status_label }}
                                    </Badge>
                                </template>
                            </TableCell>
                            <TableCell class="pe-4 text-right">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    as-child
                                    title="Visualizar/Avaliar"
                                >
                                    <Link
                                        :href="
                                            selectionRoutes.projects.show({
                                                selection: selection.id,
                                                project: project.id,
                                            }).url
                                        "
                                    >
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="projects.data.length === 0">
                            <TableCell
                                colspan="5"
                                class="h-24 text-center text-muted-foreground"
                            >
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
