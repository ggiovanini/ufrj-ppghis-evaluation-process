<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import { watch } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import selectionRoutes from '@/routes/selection';
import type { Project, ProjectWithDetail } from '@/types/projects';

const props = defineProps<{
    selectionId: number;
    project: Project | ProjectWithDetail | null;
    open: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const form = useForm({
    written_exam_score: '',
});

const sanitizeScore = (value: string): string => {
    if (value.trim() === '') {
        return '';
    }

    const digits = value.replace(/\D/g, '');

    if (digits === '') {
        return '';
    }

    const scoreInCents = Math.min(Number(digits), 1000);
    const limitedDigits = String(scoreInCents);

    if (limitedDigits.length === 1) {
        return limitedDigits;
    }

    if (limitedDigits.length === 2) {
        return `${limitedDigits[0]},${limitedDigits[1]}`;
    }

    return `${limitedDigits.slice(0, -2)},${limitedDigits.slice(-2)}`;
};

const normalizeScore = (): void => {
    const sanitizedScore = sanitizeScore(form.written_exam_score);

    if (sanitizedScore === '') {
        return;
    }

    form.written_exam_score = sanitizedScore.includes(',')
        ? sanitizedScore.padEnd(4, '0')
        : `${sanitizedScore},00`;
};

const handleScoreInput = (value: string | number): void => {
    form.written_exam_score = sanitizeScore(String(value));
};

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen && props.project) {
            form.written_exam_score =
                props.project.written_exam_score_label || '';
        }
    },
);

const submit = () => {
    if (!props.project) {
        return;
    }

    form.patch(
        selectionRoutes.projects.update({
            selection: props.selectionId,
            project: props.project.id,
        }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                emit('update:open', false);
                toast.success('Nota salva com sucesso!');
            },
            onError: () => {
                toast.error('Erro ao salvar a nota.');
            },
        },
    );
};
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="sm:max-w-106.25">
            <DialogHeader>
                <DialogTitle>
                    {{ project?.written_exam_score ? 'Editar' : 'Inserir' }}
                    Nota da Prova
                </DialogTitle>
                <DialogDescription>
                    Informe a nota da prova escrita para o candidato
                    <strong>{{ project?.candidate_name }}</strong
                    >.
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="submit">
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="score">Nota (0,00 a 10,00)</Label>
                        <Input
                            id="score"
                            :model-value="form.written_exam_score"
                            inputmode="decimal"
                            maxlength="5"
                            placeholder="Ex: 7,50"
                            @blur="normalizeScore"
                            @update:model-value="handleScoreInput"
                            autofocus
                        />
                        <div
                            v-if="form.errors.written_exam_score"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.written_exam_score }}
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="$emit('update:open', false)"
                    >
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Save class="mr-2 h-4 w-4" />
                        Salvar
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
