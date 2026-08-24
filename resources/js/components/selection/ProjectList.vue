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
    UserPlus,
    Trash2,
    CheckCircle2,
    Settings2,
    LandPlot,
    RotateCcw,
} from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { ref, computed } from 'vue';
import Pagination from '@/components/Pagination.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator,
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
import ProjectColumnTitle from '@/pages/projects/partials/ProjectColumnTitle.vue';
import selectionRoutes from '@/routes/selection';
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

const isSelectionModalOpen = ref(false);
const selectedProject = ref<Project | null>(null);
const selectedAssignment = ref<ReviewAssignment | null>(null);
const isIndicatedSlot = ref(false);
const selectedReviewerId = ref<string | null>(null);

const openSelectionModal = (
    project: Project,
    assignment?: ReviewAssignment,
    indicated = false,
) => {
    if (props.readOnly) {
        return;
    }

    selectedProject.value = project;
    selectedAssignment.value = assignment || null;
    isIndicatedSlot.value = indicated;
    selectedReviewerId.value = assignment
        ? assignment.user_id.toString()
        : null;
    isSelectionModalOpen.value = true;
};

const assignReviewer = () => {
    if (!selectedProject.value || !selectedReviewerId.value) {
        return;
    }

    router.post(
        selectionRoutes.assignments.store(props.selection.id).url,
        {
            project_id: selectedProject.value.id,
            user_id: selectedReviewerId.value,
            chosen_by_candidate: isIndicatedSlot.value,
        },
        {
            onSuccess: () => {
                isSelectionModalOpen.value = false;
            },
        },
    );
};

const removeAssignment = (project: Project, assignment: ReviewAssignment) => {
    router.delete(selectionRoutes.assignments.destroy(props.selection.id).url, {
        data: {
            project_id: project.id,
            user_id: assignment.user_id,
        },
    });
};

const autoAssignIndicated = () => {
    openConfirmation(
        'Atribuição automática (Indicados)',
        'Deseja atribuir automaticamente os avaliadores indicados pelos candidatos nos casos em que ainda não há atribuição?',
        () => {
            router.post(
                selectionRoutes.assignments.auto(props.selection.id).url,
            );
        },
        'Atribuir',
        'default',
    );
};

const autoAssign = () => {
    openConfirmation(
        'Atribuição automática (Faltantes)',
        'Deseja atribuir automaticamente avaliadores aleatórios para os projetos que ainda não possuem o número necessário de avaliadores?',
        () => {
            router.post(
                selectionRoutes.assignments.auto.complete(props.selection.id)
                    .url,
            );
        },
        'Atribuir',
        'default',
    );
};

const clearAll = () => {
    openConfirmation(
        'Remover atribuições',
        'Tem certeza que deseja remover todas as atribuições de avaliadores de todos os projetos?',
        () => {
            router.delete(
                selectionRoutes.assignments.destroyAll(props.selection.id).url,
            );
        },
        'Remover todos',
    );
};

const clearProjectAssignments = (project: Project) => {
    openConfirmation(
        'Remover avaliadores',
        'Tem certeza que deseja remover todos os avaliadores deste projeto?',
        () => {
            router.delete(
                selectionRoutes.projects.assignments.destroy({
                    selection: props.selection.id,
                    project: project.id,
                }).url,
            );
        },
        'Remover',
    );
};

const returnToHomologation = () => {
    openConfirmation(
        'Retomar homologação',
        'Deseja retornar à etapa de homologação? Os projetos voltarão para a etapa anterior e poderão ser conferidos novamente.',
        () => {
            router.post(
                selectionRoutes.returnToHomologation(props.selection.id).url,
            );
        },
        'Retomar homologação',
        'default',
    );
};

const finalize = () => {
    openConfirmation(
        'Finalizar etapa de atribuição',
        'Tem certeza que deseja finalizar a etapa de atribuição e enviar os projetos para avaliação? Os avaliadores serão notificados.',
        () => {
            router.post(selectionRoutes.finalize(props.selection.id).url);
        },
        'Enviar para avaliação',
        'default',
    );
};

const getIndicatedAssignment = (project: Project) => {
    return project.review_assignments.find((a) => a.chosen_by_candidate);
};

const getOtherAssignments = (project: Project) => {
    return project.review_assignments.filter((a) => !a.chosen_by_candidate);
};

const isFullyAssigned = computed(() => {
    return props.stats.total_assigned === props.stats.total_projects;
});
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
                            <DropdownMenuItem @click="autoAssignIndicated">
                                <Search class="mr-2 h-4 w-4" />
                                Selecionar avaliadores indicados
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="clearAll">
                                <X class="mr-2 h-4 w-4" />
                                Remover todas as indicações
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="autoAssign">
                                <LandPlot class="mr-2 h-4 w-4" />
                                Selecionar avaliadores faltantes
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem @click="returnToHomologation">
                                <RotateCcw class="mr-2 h-4 w-4" />
                                Retomar homologação
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <Button @click="finalize" :disabled="!isFullyAssigned">
                        <CheckCircle2 class="h-4 w-4" />
                        Enviar para avaliação
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
                            <TableHead>Nota</TableHead>
                            <TableHead>Avaliadores</TableHead>
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
                            <TableCell class="text-center">
                                <Badge
                                    v-if="project.review_score"
                                    variant="secondary"
                                >
                                    {{ project.review_score_label }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-wrap gap-2">
                                    <!-- Indicated Slot -->
                                    <template v-if="project.has_indication">
                                        <template
                                            v-if="
                                                getIndicatedAssignment(project)
                                            "
                                        >
                                            <Badge
                                                variant="default"
                                                class="cursor-pointer border-solid hover:bg-primary/90"
                                                @click="
                                                    openSelectionModal(
                                                        project,
                                                        getIndicatedAssignment(
                                                            project,
                                                        ),
                                                        true,
                                                    )
                                                "
                                            >
                                                {{
                                                    getIndicatedAssignment(
                                                        project,
                                                    )?.user?.name
                                                }}
                                            </Badge>
                                        </template>
                                        <template v-else>
                                            <Badge
                                                variant="outline"
                                                class="cursor-pointer border-dashed border-primary/50 text-primary hover:bg-muted"
                                                @click="
                                                    openSelectionModal(
                                                        project,
                                                        undefined,
                                                        true,
                                                    )
                                                "
                                            >
                                                <UserPlus
                                                    class="mr-1 h-3 w-3"
                                                />
                                                Indicação:
                                                {{ project.indication }}
                                            </Badge>
                                        </template>
                                    </template>

                                    <!-- Other Slots -->
                                    <template
                                        v-for="i in project.has_indication
                                            ? 2
                                            : 3"
                                        :key="i"
                                    >
                                        <template
                                            v-if="
                                                getOtherAssignments(project)[
                                                    i - 1
                                                ]
                                            "
                                        >
                                            <Badge
                                                variant="secondary"
                                                class="cursor-pointer border-solid hover:bg-muted"
                                                @click="
                                                    openSelectionModal(
                                                        project,
                                                        getOtherAssignments(
                                                            project,
                                                        )[i - 1],
                                                        false,
                                                    )
                                                "
                                            >
                                                {{
                                                    getOtherAssignments(
                                                        project,
                                                    )[i - 1].user?.name
                                                }}
                                            </Badge>
                                        </template>
                                        <template v-else>
                                            <Badge
                                                variant="outline"
                                                class="cursor-pointer border-dashed text-muted-foreground hover:bg-muted"
                                                @click="
                                                    openSelectionModal(
                                                        project,
                                                        undefined,
                                                        false,
                                                    )
                                                "
                                            >
                                                <UserPlus
                                                    class="mr-1 h-3 w-3"
                                                />
                                                Aguardando seleção
                                            </Badge>
                                        </template>
                                    </template>
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
                                            @click="
                                                navigateToShowProject(
                                                    project.id,
                                                )
                                            "
                                        >
                                            <Eye class="mr-1 h-4 w-4" /> Ver
                                            Projeto
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            class="text-destructive focus:bg-destructive focus:text-destructive-foreground"
                                            @click="
                                                clearProjectAssignments(project)
                                            "
                                        >
                                            <X class="mr-1 h-4 w-4" />
                                            Remover avaliadores
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

    <!-- Selection Modal -->
    <Dialog v-model:open="isSelectionModalOpen">
        <DialogContent class="sm:max-w-106.25">
            <DialogHeader>
                <DialogTitle>Selecionar Avaliador</DialogTitle>
                <DialogDescription>
                    Selecione um avaliador para o projeto de
                    <strong>{{ selectedProject?.candidate_name }}</strong
                    >.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-4 py-4">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium">Avaliador</label>
                    <Select v-model="selectedReviewerId">
                        <SelectTrigger>
                            <SelectValue placeholder="Selecione um avaliador" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="reviewer in reviewers"
                                :key="reviewer.id"
                                :value="reviewer.id.toString()"
                            >
                                {{ reviewer.name }} ({{
                                    reviewer.assigned_count
                                }}
                                atribuições)
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
            <DialogFooter
                class="flex items-center justify-between sm:justify-between"
            >
                <Button
                    v-if="selectedAssignment"
                    variant="destructive"
                    size="sm"
                    @click="
                        removeAssignment(selectedProject!, selectedAssignment)
                    "
                >
                    <Trash2 class="mr-1 h-4 w-4" />
                    Remover
                </Button>
                <div v-else></div>
                <div class="flex gap-2">
                    <Button
                        variant="outline"
                        @click="isSelectionModalOpen = false"
                        >Cancelar</Button
                    >
                    <Button @click="assignReviewer">Salvar Atribuição</Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Confirmation Modal -->
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
                        () => {
                            confirmationData.onConfirm();
                            isConfirmingAction = false;
                        }
                    "
                >
                    {{ confirmationData.confirmButtonText }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
