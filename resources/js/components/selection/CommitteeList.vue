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
    Trash2,
    CheckCircle2,
    Settings2,
    Dot,
    ArrowRight,
    Pencil,
} from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { ref, computed } from 'vue';
import Pagination from '@/components/Pagination.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import CommitteeScoreModal from '@/components/selection/CommitteeScoreModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import ProjectColumnTitle from '@/pages/projects/partials/ProjectColumnTitle.vue';
import selectionRoutes from '@/routes/selection';
import type { DataFilters, DataPagination } from '@/types/pagination';
import type { Project } from '@/types/projects';
import type {
    SelectionProcessStats,
    SelectionProcess,
} from '@/types/selection-process';

const props = defineProps<{
    selection: SelectionProcess;
    projects: DataPagination<Project>;
    filters?: DataFilters;
    stats: SelectionProcessStats;
    readOnly?: boolean;
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

// Distribution Logic
const isConfirmingAction = ref(false);
const confirmationData = ref<{
    title: string;
    description: string;
    confirmButtonText: string;
    variant: 'default' | 'destructive';
    onConfirm: () => void;
}>({
    title: '',
    description: '',
    confirmButtonText: 'Confirmar',
    variant: 'default',
    onConfirm: () => {},
});

const openConfirmation = (
    title: string,
    description: string,
    onConfirm: () => void,
    confirmButtonText = 'Confirmar',
    variant: 'default' | 'destructive' = 'destructive',
) => {
    confirmationData.value = {
        title,
        description,
        confirmButtonText,
        variant,
        onConfirm,
    };
    isConfirmingAction.value = true;
};

const deleteAllWrittenExam = () => {
    openConfirmation(
        'Remover todos as notas',
        'Tem certeza que deseja remover TODAS as notas? Esta ação é irreversível e removerá todas as atribuições de notas.',
        () => {
            router.delete(
                selectionRoutes.projects.deleteAll(props.selection.id).url,
            );
        },
        'Remover todas',
    );
};

const finalize = () => {
    openConfirmation(
        'Finalizar a etapa de avaliação dos comitês',
        'Tem certeza que deseja finalizar a etapa de avaliação dos comitês e enviar os projetos para os resultados?',
        () => {
            router.post(selectionRoutes.finalize(props.selection.id).url);
        },
        'Enviar para resultados',
        'default',
    );
};

const isFullyAssigned = computed(() => {
    return (
        props.stats.committee_evaluations === props.stats.committee_evaluated
    );
});

const isScoreModalOpen = ref(false);
const selectedProjectForScore = ref<Project | null>(null);

const insertScore = (project: Project) => {
    selectedProjectForScore.value = project;
    isScoreModalOpen.value = true;
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
                <div class="flex items-center gap-2" v-if="!readOnly">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="secondary">
                                <Settings2 class="h-4 w-4" />
                                Mais
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <DropdownMenuItem @click="deleteAllWrittenExam">
                                <Trash2 class="mr-2 h-4 w-4" />
                                Remover todos as notas
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <Button @click="finalize" :disabled="!isFullyAssigned">
                        <CheckCircle2 class="h-4 w-4" />
                        Liberar para resultados
                    </Button>
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
                            <TableHead>Status</TableHead>
                            <TableHead class="text-center">NMA</TableHead>
                            <TableHead class="text-center">NP</TableHead>
                            <TableHead class="text-center whitespace-nowrap"
                                >Prova oral</TableHead
                            >
                            <TableHead
                                class="flex items-center justify-end pe-4"
                                v-if="!readOnly"
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
                                <ProjectColumnTitle :project="project" />
                            </TableCell>
                            <TableCell class="pe-4 text-center">
                                <Badge
                                    :class="
                                        project.stage === 'rejected'
                                            ? 'bg-orange-300'
                                            : ''
                                    "
                                >
                                    {{ project.stage_label }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-center">
                                <Badge
                                    v-if="project.review_score"
                                    variant="secondary"
                                >
                                    {{ project.review_score_label }}
                                </Badge>
                                <Dot v-else class="mx-auto h-4 w-4" />
                            </TableCell>
                            <TableCell class="text-center">
                                <Badge
                                    v-if="
                                        project.written_exam_score &&
                                        project.modality === 'master'
                                    "
                                    variant="secondary"
                                >
                                    {{ project.written_exam_score_label }}
                                </Badge>
                                <Dot
                                    v-else-if="project.modality === 'master'"
                                    class="mx-auto h-4 w-4"
                                />
                                <ArrowRight v-else class="mx-auto h-4 w-4" />
                            </TableCell>
                            <TableCell class="text-center">
                                <div
                                    class="flex flex-row items-center justify-center"
                                >
                                    <Badge
                                        v-if="project.committee_score !== null"
                                        variant="secondary"
                                    >
                                        {{ project.committee_score_label }}
                                    </Badge>
                                    <Dot v-else class="mx-auto h-4 w-4" />
                                    <Badge
                                        v-if="project.committee_score !== null"
                                        class="ml-2"
                                        :class="
                                            project.committee_score_passes
                                                ? 'bg-green-600 text-white'
                                                : 'bg-red-600 text-white'
                                        "
                                    >
                                        {{
                                            project.committee_score_passes
                                                ? 'Aprovado'
                                                : 'Reprovado'
                                        }}
                                    </Badge>
                                </div>
                            </TableCell>
                            <TableCell class="text-right" v-if="!readOnly">
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
                                            @click="insertScore(project)"
                                        >
                                            <Pencil class="mr-1 h-4 w-4" />
                                            {{
                                                project.committee_score !== null
                                                    ? 'Editar nota da comissão'
                                                    : 'Inserir nota da comissão'
                                            }}
                                        </DropdownMenuItem>
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
                            <TableCell colspan="6" class="h-24 text-center">
                                Nenhum projeto encontrado.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            <Pagination :meta="projects.meta" class="z-10" />
        </div>
    </div>

    <CommitteeScoreModal
        v-model:open="isScoreModalOpen"
        :selection-id="selection.id"
        :project="selectedProjectForScore"
    />

    <Dialog v-model:open="isConfirmingAction">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ confirmationData.title }}</DialogTitle>
                <DialogDescription>
                    {{ confirmationData.description }}
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="isConfirmingAction = false">
                    Cancelar
                </Button>
                <Button
                    :variant="confirmationData.variant"
                    @click="
                        confirmationData.onConfirm();
                        isConfirmingAction = false;
                    "
                >
                    {{ confirmationData.confirmButtonText }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
