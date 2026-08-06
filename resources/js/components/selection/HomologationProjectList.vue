<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Asterisk,
    Check,
    CheckCircle2,
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

const props = defineProps<{
    selection: SelectionProcess;
    projects: DataPagination<Project>;
    filters?: DataFilters;
    pendingProjects: number;
}>();

const search = ref(props.filters?.search || '');

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
                            <TableHead>Candidato</TableHead>
                            <TableHead>Status</TableHead>
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
                                class="font-medium"
                                @click="navigateToShowProject(project.id)"
                            >
                                <span>{{ project.candidate_name }}</span>
                                <span
                                    class="line-clamp-1 text-xs font-normal text-muted-foreground"
                                    >#{{ project.id }} {{ project.title }}</span
                                >
                                <Badge variant="secondary">
                                    {{ project.modality_label }}
                                </Badge>
                            </TableCell>
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
                                        Aprovar
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
                                        Desaprovar
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
                            ? 'Desaprovar cadastro'
                            : 'Aprovar cadastro'
                    }}
                </DialogTitle>
                <DialogDescription>
                    Tem certeza que deseja
                    {{
                        pendingAction?.status === 'rejected'
                            ? 'desaprovar'
                            : 'aprovar'
                    }}
                    o cadastro de
                    <strong>{{ pendingAction?.project.candidate_name }}</strong
                    >?
                </DialogDescription>
            </DialogHeader>

            <div v-if="pendingAction?.status === 'rejected'" class="space-y-2">
                <Label for="homologation-reason">Motivo da desaprovação</Label>
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
