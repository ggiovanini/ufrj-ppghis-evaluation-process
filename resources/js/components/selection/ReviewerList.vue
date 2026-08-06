<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Search,
    X,
    CheckCircle2,
    Clock,
    MoreHorizontal,
    Bell,
    Trash2,
    Settings2,
    ExternalLink,
} from '@lucide/vue';
import { ref, computed } from 'vue';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import selectionRoutes from '@/routes/selection';
import teamRoutes from '@/routes/team';
import type { Reviewer } from '@/types/reviewer';
import type { SelectionProcess } from '@/types/selection-process';

const props = defineProps<{
    selection: SelectionProcess;
    reviewers: Reviewer[];
}>();

const search = ref('');

const filteredReviewers = computed(() => {
    return props.reviewers.filter(
        (reviewer) =>
            reviewer.name.toLowerCase().includes(search.value.toLowerCase()) ||
            reviewer.email.toLowerCase().includes(search.value.toLowerCase()),
    );
});

const clearSearch = () => {
    search.value = '';
};

const getProgress = (reviewer: Reviewer) => {
    if (!reviewer?.assigned_count || !reviewer?.completed_count) {
        return 0;
    }

    if (reviewer.assigned_count === 0) {
        return 0;
    }

    return Math.round(
        (reviewer.completed_count / reviewer.assigned_count) * 100,
    );
};

const getStatusVariant = (reviewer: Reviewer) => {
    const progress = getProgress(reviewer);

    if (progress === 100) {
        return 'default';
    }

    if (progress > 0) {
        return 'secondary';
    }

    return 'outline';
};

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

const notifyReviewer = (reviewer: Reviewer) => {
    router.post(
        selectionRoutes.reviews.notifyReviewer({
            selection: props.selection.id,
            reviewer: reviewer.id,
        }).url,
    );
};

const notifyAll = () => {
    openConfirmation(
        'Notificar todos os avaliadores',
        'Deseja enviar uma notificação para todos os avaliadores que possuem avaliações pendentes?',
        () => {
            router.post(
                selectionRoutes.reviews.notifyAll(props.selection.id).url,
            );
        },
        'Notificar todos',
        'default',
    );
};

const clearAll = () => {
    openConfirmation(
        'Remover avaliações',
        'Tem certeza que deseja remover TODAS as avaliações deste processo seletivo? Esta ação é irreversível.',
        () => {
            router.delete(
                selectionRoutes.reviews.destroyAll(props.selection.id).url,
            );
        },
        'Remover todos',
    );
};

const finalize = () => {
    openConfirmation(
        'Finalizar etapa',
        'Tem certeza que deseja finalizar esta etapa e avançar para a próxima? Esta ação não poderá ser desfeita.',
        () => {
            router.post(selectionRoutes.finalize(props.selection.id).url);
        },
        'Finalizar',
        'default',
    );
};

const isFullyCompleted = computed(() => {
    return (
        props.reviewers.length > 0 &&
        props.reviewers.every((r) => r.assigned_count === r.completed_count)
    );
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
                        placeholder="Filtrar avaliadores..."
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
                            <Button variant="secondary">
                                <Settings2 class="h-4 w-4" />
                                Mais
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <DropdownMenuItem @click="notifyAll">
                                <Bell class="mr-2 h-4 w-4" />
                                Notificar todos com pendências
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                @click="clearAll"
                                class="text-destructive focus:text-destructive"
                            >
                                <Trash2 class="mr-2 h-4 w-4" />
                                Remover todas as avaliações
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <Button @click="finalize" :disabled="!isFullyCompleted">
                        <CheckCircle2 class="h-4 w-4" />
                        Iniciar as provas escritas
                    </Button>
                </div>
            </div>

            <div
                class="z-10 flex w-full overflow-hidden rounded-md border bg-card outline-2 outline-foreground/10"
            >
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="ps-4">Avaliador</TableHead>
                            <TableHead class="text-center"
                                >Atribuídos</TableHead
                            >
                            <TableHead class="text-center"
                                >Concluídos</TableHead
                            >
                            <TableHead class="text-center">Progresso</TableHead>
                            <TableHead class="text-center">Status</TableHead>
                            <TableHead class="pe-4 text-right"></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="reviewer in filteredReviewers"
                            :key="reviewer.id"
                        >
                            <TableCell class="ps-4 font-medium">
                                <div class="flex flex-col">
                                    <span>{{ reviewer.name }}</span>
                                    <span
                                        class="text-xs font-normal text-muted-foreground"
                                    >
                                        {{ reviewer.email }}
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell class="text-center">
                                {{ reviewer.assigned_count }}
                            </TableCell>
                            <TableCell class="text-center">
                                {{ reviewer.completed_count }}
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
                                                width: `${getProgress(reviewer)}%`,
                                            }"
                                        />
                                    </div>
                                    <span class="text-xs tabular-nums">
                                        {{ getProgress(reviewer) }}%
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell class="pe-4 text-right">
                                <Badge :variant="getStatusVariant(reviewer)">
                                    <template
                                        v-if="getProgress(reviewer) === 100"
                                    >
                                        <CheckCircle2 class="mr-1 h-3 w-3" />
                                        Concluído
                                    </template>
                                    <template
                                        v-else-if="getProgress(reviewer) > 0"
                                    >
                                        <Clock class="mr-1 h-3 w-3" />
                                        Em andamento
                                    </template>
                                    <template v-else> Pendente </template>
                                </Badge>
                            </TableCell>
                            <TableCell class="pe-4 text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon">
                                            <MoreHorizontal class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem
                                            @click="notifyReviewer(reviewer)"
                                            :disabled="
                                                reviewer.assigned_count ===
                                                reviewer.completed_count
                                            "
                                        >
                                            <Bell class="mr-2 h-4 w-4" />
                                            Notificar pendências
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="
                                                router.visit(
                                                    teamRoutes.show(reviewer.id)
                                                        .url,
                                                )
                                            "
                                        >
                                            <ExternalLink
                                                class="mr-2 h-4 w-4"
                                            />
                                            Ver detalhes
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="filteredReviewers.length === 0">
                            <TableCell colspan="5" class="h-24 text-center">
                                Nenhum avaliador encontrado.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </div>

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
