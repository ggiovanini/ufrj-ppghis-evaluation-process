<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    Asterisk,
    AlertTriangle,
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    Check,
    CheckCircle2,
    FilePlus2,
    FileSpreadsheet,
    MoreHorizontal,
    Search,
    X,
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
import { Label } from '@/components/ui/label';
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
import ProjectColumnTitle from '@/pages/projects/partials/ProjectColumnTitle.vue';

const props = defineProps<{
    selection: SelectionProcess;
    projects: DataPagination<Project>;
    filters?: DataFilters;
    pendingProjects: number;
}>();

const search = ref(props.filters?.search || '');

const sortBy = (column: string) => {
    let direction: 'asc' | 'desc' = 'asc';

    if (props.filters?.sort === column && props.filters?.direction === 'asc') {
        direction = 'desc';
    }

    router.get(
        window.location.pathname,
        { ...props.filters, sort: column, direction },
        { preserveState: true, replace: true },
    );
};

const formatSubmittedAt = (value: string | null) => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('pt-BR', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(new Date(value));
};

watchDebounced(
    search,
    (value) => {
        router.get(
            window.location.pathname,
            { ...props.filters, search: value },
            { preserveState: true, replace: true },
        );
    },
    { debounce: 300 },
);

const clearSearch = () => {
    search.value = '';
};

const isConfirmingAction = ref(false);
const rejectionReason = ref('');
const reasonError = ref('');
const pendingAction = ref<{
    project: Project;
    status: 'approved' | 'rejected';
} | null>(null);

const requestStatus = (project: Project, status: 'approved' | 'rejected') => {
    pendingAction.value = { project, status };
    rejectionReason.value = '';
    reasonError.value = '';
    isConfirmingAction.value = true;
};

const confirmStatus = () => {
    if (!pendingAction.value) {
        return;
    }

    if (
        pendingAction.value.status === 'rejected' &&
        rejectionReason.value.trim() === ''
    ) {
        reasonError.value = 'Informe o motivo da desaprovação.';

        return;
    }

    router.patch(
        selectionRoutes.projects.homologation.update({
            selection: props.selection.id,
            project: pendingAction.value.project.id,
        }).url,
        {
            status: pendingAction.value.status,
            reason:
                pendingAction.value.status === 'rejected'
                    ? rejectionReason.value.trim()
                    : null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isConfirmingAction.value = false;
            },
        },
    );
};

const navigateToShowProject = (projectId: number) => {
    router.visit(
        selectionRoutes.projects.show({
            selection: props.selection.id,
            project: projectId,
        }).url,
    );
};

const downloadReport = () => {
    window.location.href = selectionRoutes.projects.homologation.report({
        selection: props.selection.id,
    }).url;
};

const isConfirmingFinalize = ref(false);
const finalizeMode = ref<'reviewed' | 'approve-all'>('reviewed');

const requestFinalize = (mode: 'reviewed' | 'approve-all') => {
    finalizeMode.value = mode;
    isConfirmingFinalize.value = true;
};

const confirmFinalize = () => {
    const url =
        finalizeMode.value === 'approve-all'
            ? selectionRoutes.homologation.approveAllAndFinalize(
                  props.selection.id,
              ).url
            : selectionRoutes.finalize(props.selection.id).url;

    router.post(
        url,
        {},
        {
            onSuccess: () => {
                isConfirmingFinalize.value = false;
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
                <div class="flex items-center gap-2">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="secondary" size="sm">
                                <MoreHorizontal class="h-4 w-4" />
                                Mais
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem as-child>
                                <Link
                                    :href="
                                        selectionRoutes.prepare(
                                            props.selection.id,
                                        ).url
                                    "
                                >
                                    <FilePlus2 class="mr-2 h-4 w-4" />
                                    Importar mais projetos
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                @click="requestFinalize('approve-all')"
                            >
                                <CheckCircle2 class="mr-2 h-4 w-4" />
                                Aprovar pendentes e iniciar distribuição
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="downloadReport">
                                <FileSpreadsheet class="mr-2 h-4 w-4" />
                                Exportar relatório (.xlsx)
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <Button
                        :disabled="pendingProjects > 0"
                        @click="requestFinalize('reviewed')"
                    >
                        <CheckCircle2 class="h-4 w-4" />
                        Iniciar a distribuição
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
                                <div class="flex items-center gap-2">
                                    Candidato
                                    <ArrowUp
                                        v-if="
                                            filters?.sort ===
                                                'candidate_name' &&
                                            filters?.direction === 'asc'
                                        "
                                        class="h-4 w-4"
                                    />
                                    <ArrowDown
                                        v-else-if="
                                            filters?.sort === 'candidate_name'
                                        "
                                        class="h-4 w-4"
                                    />
                                    <ArrowUpDown
                                        v-else
                                        class="h-4 w-4 text-muted-foreground/50"
                                    />
                                </div>
                            </TableHead>
                            <TableHead
                                class="cursor-pointer whitespace-nowrap hover:bg-muted/50"
                                @click="sortBy('submitted_at')"
                            >
                                <div class="flex items-center gap-2">
                                    Data de envio
                                    <ArrowUp
                                        v-if="
                                            filters?.sort === 'submitted_at' &&
                                            filters?.direction === 'asc'
                                        "
                                        class="h-4 w-4"
                                    />
                                    <ArrowDown
                                        v-else-if="
                                            filters?.sort === 'submitted_at'
                                        "
                                        class="h-4 w-4"
                                    />
                                    <ArrowUpDown
                                        v-else
                                        class="h-4 w-4 text-muted-foreground/50"
                                    />
                                </div>
                            </TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-muted/50"
                                @click="sortBy('status')"
                            >
                                <div class="flex items-center gap-2">
                                    Status
                                    <ArrowUp
                                        v-if="
                                            filters?.sort === 'status' &&
                                            filters?.direction === 'asc'
                                        "
                                        class="h-4 w-4"
                                    />
                                    <ArrowDown
                                        v-else-if="filters?.sort === 'status'"
                                        class="h-4 w-4"
                                    />
                                    <ArrowUpDown
                                        v-else
                                        class="h-4 w-4 text-muted-foreground/50"
                                    />
                                </div>
                            </TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-muted/50"
                                @click="sortBy('duplicates')"
                            >
                                <div
                                    class="flex items-center gap-2 whitespace-nowrap"
                                >
                                    Duplicidade
                                    <ArrowUp
                                        v-if="
                                            filters?.sort === 'duplicates' &&
                                            filters?.direction === 'asc'
                                        "
                                        class="h-4 w-4"
                                    />
                                    <ArrowDown
                                        v-else-if="
                                            filters?.sort === 'duplicates'
                                        "
                                        class="h-4 w-4"
                                    />
                                    <ArrowUpDown
                                        v-else
                                        class="h-4 w-4 text-muted-foreground/50"
                                    />
                                </div>
                            </TableHead>
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
                            :class="
                                project.potential_duplicate
                                    ? 'bg-amber-50/50 dark:bg-amber-950/10'
                                    : undefined
                            "
                        >
                            <TableCell
                                class="font-medium"
                                @click="navigateToShowProject(project.id)"
                            >
                                <ProjectColumnTitle :project="project" />
                            </TableCell>
                            <TableCell class="text-center whitespace-nowrap">{{
                                formatSubmittedAt(project.submitted_at)
                            }}</TableCell>
                            <TableCell>
                                <Badge
                                    :variant="
                                        project.homologation_status ===
                                        'approved'
                                            ? 'default'
                                            : project.homologation_status ===
                                                'rejected'
                                              ? 'destructive'
                                              : 'secondary'
                                    "
                                >
                                    {{ project.homologation_status_label }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <div
                                    v-if="project.potential_duplicate"
                                    class="flex flex-col gap-1"
                                >
                                    <div
                                        class="flex items-center gap-1 text-center"
                                    >
                                        <Badge
                                            variant="outline"
                                            class="border-amber-500 text-amber-700"
                                        >
                                            <AlertTriangle
                                                class="h-4 w-4 text-amber-600"
                                            />
                                            {{ project.duplicate_group }}
                                        </Badge>
                                    </div>
                                </div>
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </TableCell>
                            <TableCell>
                                <div class="flex justify-end gap-2">
                                    <Button
                                        size="sm"
                                        :variant="
                                            project.homologation_status ===
                                            'approved'
                                                ? 'default'
                                                : 'outline'
                                        "
                                        @click="
                                            requestStatus(project, 'approved')
                                        "
                                    >
                                        <Check class="h-4 w-4" />
                                        Homologar
                                    </Button>
                                    <Button
                                        size="sm"
                                        :variant="
                                            project.homologation_status ===
                                            'rejected'
                                                ? 'destructive'
                                                : 'outline'
                                        "
                                        @click="
                                            requestStatus(project, 'rejected')
                                        "
                                    >
                                        <X class="h-4 w-4" />
                                        Não homologar
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            <Pagination :meta="projects.meta" class="z-10" />
        </div>
    </div>

    <Dialog v-model:open="isConfirmingAction">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>
                    {{
                        pendingAction?.status === 'rejected'
                            ? 'Não homologar cadastro'
                            : 'Homologar cadastro'
                    }}
                </DialogTitle>
                <DialogDescription>
                    Tem certeza que deseja
                    {{
                        pendingAction?.status === 'rejected'
                            ? 'não homologar'
                            : 'homologar'
                    }}
                    o cadastro de
                    <strong>{{ pendingAction?.project.candidate_name }}</strong
                    >?
                </DialogDescription>
            </DialogHeader>

            <div v-if="pendingAction?.status === 'rejected'" class="space-y-2">
                <Label for="homologation-reason">Motivo</Label>
                <textarea
                    id="homologation-reason"
                    v-model="rejectionReason"
                    placeholder="Informe o motivo..."
                    rows="4"
                    class="flex min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                />
                <p v-if="reasonError" class="text-sm text-destructive">
                    {{ reasonError }}
                </p>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="isConfirmingAction = false">
                    Cancelar
                </Button>
                <Button
                    :variant="
                        pendingAction?.status === 'rejected'
                            ? 'destructive'
                            : 'default'
                    "
                    @click="confirmStatus"
                >
                    Confirmar
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="isConfirmingFinalize">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>
                    {{
                        finalizeMode === 'approve-all'
                            ? 'Aprovar pendentes e finalizar'
                            : 'Finalizar homologação'
                    }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        finalizeMode === 'approve-all'
                            ? 'Os projetos pendentes serão aprovados e a etapa de distribuição será iniciada.'
                            : 'Todos os projetos já foram decididos. Deseja iniciar a etapa de distribuição?'
                    }}
                </DialogDescription>
            </DialogHeader>

            <DialogFooter>
                <Button variant="outline" @click="isConfirmingFinalize = false">
                    Cancelar
                </Button>
                <Button @click="confirmFinalize">Confirmar</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
