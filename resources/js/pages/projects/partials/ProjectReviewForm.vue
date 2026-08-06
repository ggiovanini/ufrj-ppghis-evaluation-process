<script setup lang="ts">
import { usePage, useForm } from '@inertiajs/vue3';
import { Save, Send } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import evaluateRoutes from '@/routes/selection/evaluate';
import type { ProjectWithDetail } from '@/types/projects';
import type { ReviewScoreOptions } from '@/types/reviewer';

const props = defineProps<{
    project: ProjectWithDetail;
    reviewScoreOptions: ReviewScoreOptions;
    open: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const page = usePage();
const auth = page.props.auth;
const selection = (page.props.auth as any).currentSelectionProcess;
const reviewFormSchema = selection?.review_form?.schema?.fields || [];

const currentAssignment = computed(() =>
    props.project.review_assignments.data.find(
        (a) => a.user.id === auth.user.id,
    ),
);

const form = useForm({
    score: currentAssignment.value?.review?.score ?? 0,
    comments: currentAssignment.value?.review?.comments ?? '',
    questions: currentAssignment.value?.review?.questions ?? '',
    answers: currentAssignment.value?.review?.answers || {},
    status: 'draft',
});

interface Field {
    id: number;
    label: string;
    type: 'select' | 'text' | 'textarea';
    required: boolean;
    options: string[];
}

// Inicializar answers com chaves para cada questão se não existirem
watch(
    () => props.open,
    (isOpen) => {
        if (isOpen && Object.keys(form.answers).length === 0) {
            const initialAnswers: string[] = [];
            reviewFormSchema.forEach((field: Field) => {
                initialAnswers[field.id] = '';
            });
            form.answers = initialAnswers;
        }
    },
    { immediate: true },
);

// Sincronizar rascunho se ele mudar (ex: após salvar)
watch(
    () => currentAssignment.value?.review,
    (review) => {
        if (review && !form.processing) {
            form.score = review.score;
            form.comments = review.comments;
            form.answers = review.answers || {};
        }
    },
    { deep: true },
);

const isConfirming = ref(false);
const confirmStatus = ref<'draft' | 'submitted'>('draft');

const isValidForSubmission = computed(() => {
    const hasScore =
        form.score !== 0 && form.score !== null;
    const allRequiredFieldsFilled = reviewFormSchema.every((field: Field) => {
        if (!field.required) {
            return true;
        }

        const answer = form.answers[field.id];

        return (
            answer !== undefined &&
            answer !== null &&
            String(answer).trim() !== ''
        );
    });

    return hasScore && allRequiredFieldsFilled;
});

const confirmMessages = {
    draft: {
        title: 'Salvar rascunho?',
        description: 'Você pode editar esta avaliação mais tarde.',
        action: 'Salvar',
    },
    submitted: {
        title: 'Submeter avaliação?',
        description:
            'Depois de enviada, ela não poderá mais ser editada. Tem certeza que deseja prosseguir?',
        action: 'Submeter',
    },
};

const openConfirmation = (status: 'draft' | 'submitted') => {
    if (status === 'submitted' && !isValidForSubmission.value) {
        toast.error(
            'Por favor, preencha todos os campos obrigatórios e selecione uma recomendação.',
        );

        return;
    }

    confirmStatus.value = status;
    isConfirming.value = true;
};

const executeSubmit = () => {
    isConfirming.value = false;
    form.status = confirmStatus.value;

    form.post(
        evaluateRoutes.store({
            selection: selection?.id,
            project: props.project.id,
        }).url,
        {
            onSuccess: () => {
                emit('update:open', false);
                toast.success(
                    confirmStatus.value === 'submitted'
                        ? 'Avaliação enviada!'
                        : 'Rascunho salvo!',
                );
            },
            onError: () => {
                toast.error('Erro ao salvar avaliação.');
            },
        },
    );
};
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="flex max-h-[90vh] flex-col sm:max-w-4xl">
            <DialogHeader>
                <DialogTitle>Avaliação do Projeto</DialogTitle>
                <DialogDescription>
                    Preencha os campos abaixo para avaliar o projeto de
                    {{ project.candidate_name }}.
                </DialogDescription>
            </DialogHeader>

            <div class="flex-1 overflow-y-auto px-1 py-4 pe-6">
                <div class="grid gap-6">
                    <!-- Perguntas Dinâmicas do Formulário -->
                    <div
                        v-for="field in reviewFormSchema"
                        :key="field.id"
                        class="grid gap-2 border-b pb-4 last:border-0"
                    >
                        <Label
                            :for="'field-' + field.id"
                            class="text-base leading-tight"
                        >
                            {{ field.label }}
                            <span v-if="field.required" class="text-destructive"
                                >*</span
                            >
                        </Label>

                        <template v-if="field.type === 'select'">
                            <Select v-model="form.answers[field.id]">
                                <SelectTrigger :id="'field-' + field.id">
                                    <SelectValue
                                        placeholder="Selecione uma opção"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in field.options"
                                        :key="option"
                                        :value="option"
                                    >
                                        {{ option }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </template>

                        <template v-else-if="field.type === 'text'">
                            <textarea
                                :id="'field-' + field.id"
                                v-model="form.answers[field.id]"
                                class="flex min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                :placeholder="
                                    field.placeholder || 'Sua resposta...'
                                "
                            ></textarea>
                        </template>
                    </div>

                    <div class="grid grid-cols-1 gap-4 pt-4">
                        <div class="grid gap-2 md:col-span-3">
                            <Label class="text-xl" for="score"
                                >Recomendação</Label
                            >
                            <Select v-model="form.score">
                                <SelectTrigger id="score">
                                    <SelectValue
                                        placeholder="Escolha sua recomendação"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in reviewScoreOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-sm text-muted">
                                Escolha a recomendação que seja mais apropriada para o projeto levando em consideração os pontos fortes e frágeis do projeto.
                            </p>
                            <div
                                v-if="form.errors.score"
                                class="text-xs text-destructive"
                            >
                                {{ form.errors.score }}
                            </div>
                        </div>

                        <div class="grid gap-2 md:col-span-3">
                            <Label for="comments"
                                >Justificativa do parecer</Label
                            >
                            <textarea
                                id="comments"
                                v-model="form.comments"
                                class="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="Indique, se apropriado, os pontos mais fortes e mais frágeis do projeto. Seu parecer detalhado será de grande auxílio para a banca de avaliação."
                            ></textarea>
                            <div
                                v-if="form.errors.comments"
                                class="text-xs text-destructive"
                            >
                                {{ form.errors.comments }}
                            </div>
                        </div>

                        <div class="grid gap-2 md:col-span-3">
                            <Label for="comments"
                                >Que pergunta sobre este projeto você sugere que
                                a banca faça a/ao candidato/a, caso este/a
                                chegue à prova oral?
                            </Label>
                            <textarea
                                id="questions"
                                v-model="form.questions"
                                class="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="Digite perguntas que serão úteis para a banca de avaliação."
                            ></textarea>
                            <div
                                v-if="form.errors.questions"
                                class="text-xs text-destructive"
                            >
                                {{ form.errors.questions }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter
                class="flex flex-col gap-2 border-t pt-4 sm:flex-row sm:justify-between"
            >
                <Button
                    type="button"
                    variant="outline"
                    @click="openConfirmation('draft')"
                    :disabled="form.processing"
                >
                    <Save class="mr-2 h-4 w-4" />
                    Salvar Rascunho
                </Button>
                <Button
                    type="button"
                    variant="default"
                    @click="openConfirmation('submitted')"
                    :disabled="form.processing || !isValidForSubmission"
                >
                    <Send class="mr-2 h-4 w-4" />
                    Submeter Avaliação
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Diálogo de Confirmação -->
    <Dialog v-model:open="isConfirming">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{
                    confirmMessages[confirmStatus].title
                }}</DialogTitle>
                <DialogDescription>
                    {{ confirmMessages[confirmStatus].description }}
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="isConfirming = false">
                    Cancelar
                </Button>
                <Button
                    :variant="
                        confirmStatus === 'submitted' ? 'default' : 'secondary'
                    "
                    @click="executeSubmit"
                >
                    {{ confirmMessages[confirmStatus].action }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
