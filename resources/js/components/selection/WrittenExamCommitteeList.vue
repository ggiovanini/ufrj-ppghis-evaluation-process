<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Eye, MoreHorizontal, Pencil, Search, X } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { ref } from 'vue';
import Pagination from '@/components/Pagination.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import WrittenExamScoreModal from '@/components/selection/WrittenExamScoreModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import selectionRoutes from '@/routes/selection';
import type { DataFilters, DataPagination } from '@/types/pagination';
import type { Project } from '@/types/projects';
import type { SelectionProcess } from '@/types/selection-process';

const props = defineProps<{
    selection: SelectionProcess;
    projects: DataPagination<Project>;
    filters?: DataFilters;
}>();

const search = ref(props.filters?.search || '');

watchDebounced(search, (value) => {
    router.get(window.location.pathname, { ...props.filters, search: value, page: 1 }, {
        preserveState: true,
        replace: true,
    });
}, { debounce: 300 });

const clearSearch = (): void => {
    search.value = '';
};

const navigateToShowProject = (projectId: number): void => {
    router.visit(selectionRoutes.projects.show({ selection: props.selection.id, project: projectId }).url);
};

const isScoreModalOpen = ref(false);
const selectedProjectForScore = ref<Project | null>(null);

const insertScore = (project: Project): void => {
    selectedProjectForScore.value = project;
    isScoreModalOpen.value = true;
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="relative flex w-full flex-col overflow-hidden rounded-xl border p-2">
            <PlaceholderPattern class="z-0" />
            <div class="z-10 mb-2 flex items-center justify-between gap-4 p-2">
                <div class="relative w-full max-w-sm">
                    <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="Filtrar projetos..." class="pl-10" />
                    <button v-if="search" class="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground" @click="clearSearch">
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>
            <div class="z-10 flex w-full overflow-hidden rounded-md border bg-card outline-2 outline-foreground/10">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Candidato</TableHead>
                            <TableHead>Título do projeto</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="pe-4 text-right">Ações</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="project in projects.data" :key="project.id">
                            <TableCell class="ps-4 font-medium">{{ project.candidate_name }}</TableCell>
                            <TableCell>{{ project.title }}</TableCell>
                            <TableCell>
                                <Badge :class="project.written_exam_score !== null ? 'bg-green-500' : 'bg-muted-foreground'">
                                    {{ project.written_exam_score !== null ? 'Nota registrada' : 'Pendente' }}
                                </Badge>
                            </TableCell>
                            <TableCell class="pe-4 text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" class="h-8 w-8 p-0"><MoreHorizontal class="h-4 w-4" /></Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem @click="insertScore(project)">
                                            <Pencil class="mr-1 h-4 w-4" />
                                            {{ project.written_exam_score !== null ? 'Editar nota da prova' : 'Inserir nota da prova' }}
                                        </DropdownMenuItem>
                                        <DropdownMenuItem @click="navigateToShowProject(project.id)">
                                            <Eye class="mr-1 h-4 w-4" /> Ver projeto
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="projects.data.length === 0">
                            <TableCell colspan="4" class="h-24 text-center">Nenhum projeto encontrado.</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            <Pagination :meta="projects.meta" class="z-10" />
        </div>
    </div>

    <WrittenExamScoreModal v-model:open="isScoreModalOpen" :selection-id="selection.id" :project="selectedProjectForScore" />
</template>
