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
    committee_score: '',
    comments: '',
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
    const sanitizedScore = sanitizeScore(form.committee_score);

    if (sanitizedScore === '') {
        return;
    }

    form.committee_score = sanitizedScore.includes(',')
        ? sanitizedScore.padEnd(4, '0')
        : `${sanitizedScore},00`;
};

const handleScoreInput = (value: string | number): void => {
    form.committee_score = sanitizeScore(String(value));
};

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen && props.project) {
            form.committee_score = props.project.committee_score_label || '';
            form.comments = props.project.committee_evaluation?.comments || '';
            form.clearErrors();
        }
    },
);

const submit = (): void => {
    if (!props.project) {
        return;
    }

    form.patch(
        selectionRoutes.projects.committeeScore.update({
            selection: props.selectionId,
            project: props.project.id,
        }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                emit('update:open', false);
                toast.success('Nota da comissão salva com sucesso!');
            },
            onError: () => {
                toast.error('Erro ao salvar a nota da comissão.');
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
                    {{
                        project && project.committee_score !== null
                            ? 'Editar'
                            : 'Inserir'
                    }}
                    Nota da Comissão
                </DialogTitle>
                <DialogDescription>
                    Informe a nota da comissão para o candidato
                    <strong>{{ project?.candidate_name }}</strong
                    >.
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="submit">
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="committee-score">Nota (0,00 a 10,00)</Label>
                        <Input
                            id="committee-score"
                            :model-value="form.committee_score"
                            inputmode="decimal"
                            maxlength="5"
                            placeholder="Ex: 7,50"
                            @blur="normalizeScore"
                            @update:model-value="handleScoreInput"
                            autofocus
                        />
                        <div
                            v-if="form.errors.committee_score"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.committee_score }}
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="committee-comments">Justificativa</Label>
                        <textarea
                            id="committee-comments"
                            v-model="form.comments"
                            class="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                            placeholder="Informe a justificativa da nota"
                            maxlength="1000"
                            required
                        ></textarea>
                        <div
                            v-if="form.errors.comments"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.comments }}
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
