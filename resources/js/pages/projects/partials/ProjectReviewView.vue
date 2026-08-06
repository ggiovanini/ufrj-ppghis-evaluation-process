<script setup lang="ts">
import { Download } from '@lucide/vue';
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
import type { ProjectWithDetail } from '@/types/projects';
import type { ReviewAssignment } from '@/types/reviewer';

const props = defineProps<{
    assignment: ReviewAssignment | null;
    project: ProjectWithDetail;
    open: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

interface Field {
    id: number | string;
    label: string;
    type: 'select' | 'text' | 'textarea';
    required: boolean;
    options: string[];
}

const reviewFormSchema = () =>
    (props.assignment?.review?.form?.schema?.fields || []) as Field[];

const answerFor = (field: Field): string => {
    const answer = props.assignment?.review?.answers[field.id];

    if (answer === null || answer === undefined || answer === '') {
        return 'Não respondido';
    }

    return Array.isArray(answer) ? answer.join(', ') : String(answer);
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            class="flex max-h-[90vh] flex-col sm:max-w-4xl"
            v-if="assignment"
        >
            <DialogHeader>
                <DialogTitle>Visualizar Parecer</DialogTitle>
                <DialogDescription>
                    Parecer de {{ assignment.user.name }} para o projeto.
                </DialogDescription>
            </DialogHeader>

            <div class="flex-1 overflow-y-auto px-1 py-4 pe-6">
                <div class="grid gap-6">
                    <!-- Perguntas Dinâmicas do Formulário -->
                    <div
                        v-for="field in reviewFormSchema()"
                        :key="field.id"
                        class="grid gap-2 border-b pb-4 last:border-0"
                    >
                        <Label class="text-base leading-tight font-semibold">
                            {{ field.label }}
                        </Label>

                        <div class="rounded-md bg-muted/50 p-3 text-sm">
                            {{ answerFor(field) }}
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 pt-4">
                        <div class="grid gap-2">
                            <Label class="text-lg font-semibold"
                                >Recomendação</Label
                            >
                            <div
                                class="rounded-md bg-muted/50 p-3 text-sm font-medium"
                            >
                                {{ assignment.review?.score_label }}
                                <p
                                    v-if="assignment.review?.score_description"
                                    class="mt-1 text-xs font-normal text-muted-foreground"
                                >
                                    {{ assignment.review.score_description }}
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label class="text-lg font-semibold"
                                >Justificativa do parecer</Label
                            >
                            <div
                                class="rounded-md bg-muted/50 p-3 text-sm whitespace-pre-wrap"
                            >
                                {{
                                    assignment.review?.comments ||
                                    'Nenhuma justificativa fornecida.'
                                }}
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label class="text-lg font-semibold">
                                Pergunta sugerida para a prova oral
                            </Label>
                            <div
                                class="rounded-md bg-muted/50 p-3 text-sm whitespace-pre-wrap"
                            >
                                {{
                                    assignment.review?.questions ||
                                    'Nenhuma pergunta sugerida.'
                                }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter class="border-t pt-4">
                <Button
                    v-if="assignment.review?.pdf_url"
                    as-child
                    type="button"
                    variant="outline"
                >
                    <a :href="assignment.review.pdf_url" download>
                        <Download class="h-4 w-4" />
                        Baixar PDF
                    </a>
                </Button>
                <span v-else class="text-sm text-muted-foreground">
                    O PDF será disponibilizado após o envio da avaliação.
                </span>
                <Button
                    type="button"
                    variant="outline"
                    @click="emit('update:open', false)"
                >
                    Fechar
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
