<script setup lang="ts">
import { usePage, router } from '@inertiajs/vue3';
import { ChevronDown, Check } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import selection from '@/routes/selection';

const page = usePage();
const currentProcess = computed(() => page.props.auth.currentSelectionProcess);
const processes = computed(
    () => (page.props.selectionProcesses as any[]) || [],
);
const hasMultipleProcesses = computed(() => processes.value.length > 1);

const selectProcess = (id: number) => {
    if (id === currentProcess.value?.id) {
        return;
    }

    router.post(
        selection.select().url,
        {
            selection_process_id: id,
        },
        {
            preserveScroll: true,
        },
    );
};

const phaseLabels: Record<string, string> = {
    IMPORT: 'Importação',
    HOMOLOGATION: 'Homologação',
    DISTRIBUTION: 'Distribuição',
    REVIEW: 'Avaliação',
    WRITTEN_EXAM: 'Prova Escrita',
    COMMITTEE: 'Comitê',
    RESULTS: 'Resultados',
    FINISHED: 'Finalizado',
};

const currentPhaseLabel = computed(() => {
    if (!currentProcess.value) {
        return '';
    }

    return (
        phaseLabels[currentProcess.value.phase] || currentProcess.value.phase
    );
});
</script>

<template>
    <div v-if="currentProcess" class="flex items-center">
        <DropdownMenu v-if="hasMultipleProcesses">
            <DropdownMenuTrigger as-child>
                <Button
                    variant="ghost"
                    class="flex h-9 items-center gap-2 px-3 py-2 text-sm font-medium"
                >
                    <div class="flex flex-col items-start leading-tight">
                        <span class="text-xs text-muted-foreground"
                            >Processo seletivo</span
                        >
                        <span
                            >{{ currentProcess.year }}
                            <Badge variant="secondary">{{
                                currentPhaseLabel
                            }}</Badge></span
                        >
                    </div>
                    <ChevronDown class="h-4 w-4 opacity-50" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" class="w-64">
                <DropdownMenuItem
                    v-for="proc in processes"
                    :key="proc.id"
                    @click="selectProcess(proc.id)"
                    class="flex items-center justify-between"
                >
                    <div class="flex flex-col">
                        <span class="font-medium">{{ proc.name }}</span>
                        <span class="text-xs text-muted-foreground">
                            {{ proc.year }}
                            <Badge variant="secondary">
                                {{ phaseLabels[proc.phase] || proc.phase }}
                            </Badge>
                        </span>
                    </div>
                    <Check
                        v-if="proc.id === currentProcess.id"
                        class="h-4 w-4"
                    />
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
        <div
            v-else
            class="flex h-9 items-center gap-2 px-3 py-2 text-sm font-medium"
        >
            <div class="flex flex-col items-start leading-tight">
                <span class="text-xs text-muted-foreground"
                    >Processo Seletivo</span
                >
                <span>{{ currentProcess.year }} - {{ currentPhaseLabel }}</span>
            </div>
        </div>
    </div>
</template>
