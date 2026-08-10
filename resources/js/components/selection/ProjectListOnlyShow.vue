<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
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
    UserCog,
    Trash2,
    ArrowRight,
    Dot,
} from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { ref } from 'vue';
import Pagination from '@/components/Pagination.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import selectionRoutes from '@/routes/selection';
import teamRoutes from '@/routes/team';
import { authCan } from '@/types';
import type { DataFilters, DataPagination } from '@/types/pagination';
import type { Project } from '@/types/projects';
import type { ReviewAssignment, Reviewer } from '@/types/reviewer';
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
    userId?: number;
}>();

const page = usePage();
const canReassign = authCan(page.props.auth, 'users.manage');
const isReassignModalOpen = ref(false);
const selectedProject = ref<Project | null>(null);
const selectedAssignment = ref<ReviewAssignment | null>(null);
const selectedReviewerId = ref<string | null>(null);
const isRemoveModalOpen = ref(false);
const projectToRemove = ref<Project | null>(null);
const assignmentToRemove = ref<ReviewAssignment | null>(null);

const search = ref(props.filters?.search || '');
const selectedStatus = ref(props.filters?.status || 'all');
const selectedModality = ref(props.filters?.modality || 'all');

const statusOptions = [
    { value: 'imported', label: 'Em homologação' },
    { value: 'homologated', label: 'Em distribuição' },
    { value: 'review', label: 'Em avaliação' },
    { value: 'written_exam', label: 'Em aplicação de prova' },
    { value: 'committee', label: 'Em avaliação do comitê' },
    { value: 'finished', label: 'Aprovado' },
    { value: 'rejected', label: 'Reprovado' },
];

const modalityOptions = [
    { value: 'master', label: 'Mestrado' },
    { value: 'doctorate', label: 'Doutorado' },
];

const updateFilter = (filter: 'status' | 'modality', value: unknown) => {
    if (typeof value !== 'string') {
        return;
    }

    if (filter === 'status') {
        selectedStatus.value = value;
    } else {
        selectedModality.value = value;
    }

    router.get(
        window.location.pathname,
        {
            ...props.filters,
            [filter]: value === 'all' ? undefined : value,
            page: undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

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

const openReassignModal = (project: Project) => {
    if (isProjectEvaluated(project)) {
        return;
    }

    selectedProject.value = project;
    selectedAssignment.value =
        project.review_assignments.find(
            (assignment) => assignment.user_id === props.userId,
        ) ?? null;
    selectedReviewerId.value = null;
    isReassignModalOpen.value = true;
};

const isProjectEvaluated = (project: Project): boolean => {
    return project.review_assignments.some(
        (assignment) => assignment.review?.status === 'submitted',
    );
};

const getCurrentAssignment = (project: Project): ReviewAssignment | null => {
    return (
        project.review_assignments.find(
            (assignment) => assignment.user_id === props.userId,
        ) ?? null
    );
};

const canRemoveCurrentEvaluation = (project: Project): boolean => {
    return getCurrentAssignment(project)?.review?.status === 'submitted';
};

const openRemoveModal = (project: Project) => {
    if (!canRemoveCurrentEvaluation(project)) {
        return;
    }

    projectToRemove.value = project;
    assignmentToRemove.value = getCurrentAssignment(project);
    isRemoveModalOpen.value = true;
};

const removeAssignment = () => {
    if (!assignmentToRemove.value || props.userId === undefined) {
        return;
    }

    router.delete(
        teamRoutes.assignments.destroy({
            user: props.userId,
            assignment: assignmentToRemove.value.id,
        }).url,
        {
            onSuccess: () => {
                isRemoveModalOpen.value = false;
            },
        },
    );
};

const reassignReviewer = () => {
    if (!selectedAssignment.value || !selectedReviewerId.value) {
        return;
    }

    router.post(
        teamRoutes.assignments.reassign({
            user: props.userId!,
            assignment: selectedAssignment.value.id,
        }).url,
        { reviewer_id: selectedReviewerId.value },
        {
            onSuccess: () => {
                isReassignModalOpen.value = false;
            },
        },
    );
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div
            class="relative flex w-full flex-col overflow-hidden rounded-xl border p-2"
        >
            <PlaceholderPattern class="z-0" />
            <div class="z-10 mb-2 flex flex-col gap-3 p-2 lg:flex-row lg:items-center">
                <div class="relative w-full lg:max-w-sm">
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
                <Select
                    :model-value="selectedStatus"
                    @update:model-value="(value) => updateFilter('status', value)"
                >
                    <SelectTrigger class="w-full lg:w-56">
                        <SelectValue placeholder="Todos os status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todos os status</SelectItem>
                        <SelectItem
                            v-for="option in statusOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Select
                    :model-value="selectedModality"
                    @update:model-value="(value) => updateFilter('modality', value)"
                >
                    <SelectTrigger class="w-full lg:w-48">
                        <SelectValue placeholder="Todas as modalidades" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todas as modalidades</SelectItem>
                        <SelectItem
                            v-for="option in modalityOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
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
                            <TableHead class="text-center">Status</TableHead>
                            <TableHead
                                v-if="selection.phase === 'REVIEW'"
                                class="text-center"
                                >Progresso</TableHead
                            >
                            <TableHead v-if="selection.phase === 'REVIEW'"
                                >Avaliadores</TableHead
                            >
                            <TableHead class="text-center whitespace-nowrap"
                                >NMA</TableHead
                            >
                            <TableHead class="text-center whitespace-nowrap"
                                >NP</TableHead
                            >
                            <TableHead class="text-center whitespace-nowrap"
                                >NC</TableHead
                            >
                            <TableHead class="text-center whitespace-nowrap"
                                >NF</TableHead
                            >
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
                            <TableCell class="pe-4 text-center">
                                <Badge
                                    v-if="project.stage === 'rejected'"
                                    class="bg-orange-300"
                                >
                                    {{ project.stage_label }}: {{ project.rejected_on_stage_label }}
                                </Badge>
                                <Badge v-else>
                                    {{ project.stage_label }}
                                </Badge>
                            </TableCell>
                            <TableCell
                                v-if="selection.phase === 'REVIEW'"
                                class="text-center"
                            >
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
                            <TableCell v-if="selection.phase === 'REVIEW'">
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
                                <Badge
                                    v-if="project.committee_score"
                                    variant="secondary"
                                >
                                    {{ project.committee_score_label }}
                                </Badge>
                                <Dot v-else class="mx-auto h-4 w-4" />
                            </TableCell>
                            <TableCell class="text-center">
                                <Badge
                                    v-if="project.final_score"
                                    variant="secondary"
                                >
                                    {{ project.final_score_label }}
                                </Badge>
                                <Dot v-else class="mx-auto h-4 w-4" />
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
                                        <DropdownMenuItem
                                            v-if="
                                                canReassign &&
                                                userId !== undefined &&
                                                project.review_assignments.some(
                                                    (assignment) =>
                                                        assignment.user_id ===
                                                        userId,
                                                )
                                            "
                                            :disabled="
                                                isProjectEvaluated(project)
                                            "
                                            @click="openReassignModal(project)"
                                        >
                                            <UserCog class="mr-1 h-4 w-4" />
                                            Reatribuir
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            v-if="
                                                canReassign &&
                                                userId !== undefined &&
                                                project.review_assignments.some(
                                                    (assignment) =>
                                                        assignment.user_id ===
                                                        userId,
                                                ) &&
                                                canRemoveCurrentEvaluation(
                                                    project,
                                                )
                                            "
                                            class="text-destructive focus:bg-destructive focus:text-destructive-foreground"
                                            @click="openRemoveModal(project)"
                                        >
                                            <Trash2 class="mr-1 h-4 w-4" />
                                            Remover avaliação
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

    <Dialog v-model:open="isReassignModalOpen">
        <DialogContent class="sm:max-w-106.25">
            <DialogHeader>
                <DialogTitle>Reatribuir avaliador</DialogTitle>
                <DialogDescription>
                    Escolha o novo avaliador para o projeto de
                    <strong>{{ selectedProject?.candidate_name }}</strong
                    >.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-4 py-4">
                <label class="text-sm font-medium">Novo avaliador</label>
                <Select v-model="selectedReviewerId">
                    <SelectTrigger>
                        <SelectValue placeholder="Selecione um avaliador" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="reviewer in reviewers.filter(
                                (reviewer) =>
                                    reviewer.id !== selectedAssignment?.user_id,
                            )"
                            :key="reviewer.id"
                            :value="reviewer.id.toString()"
                        >
                            {{ reviewer.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="isReassignModalOpen = false">
                    Cancelar
                </Button>
                <Button
                    :disabled="!selectedReviewerId"
                    @click="reassignReviewer"
                >
                    Confirmar troca
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="isRemoveModalOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Remover avaliação</DialogTitle>
                <DialogDescription>
                    Tem certeza que deseja remover a avaliação de
                    <strong>{{ projectToRemove?.candidate_name }}</strong
                    >? Essa ação removerá a atribuição deste avaliador.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="isRemoveModalOpen = false">
                    Cancelar
                </Button>
                <Button variant="destructive" @click="removeAssignment">
                    Remover avaliação
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
