<script setup lang="ts">
import {
    CheckCircle,
    AlertCircle,
    ClipboardCheck,
    PenTool,
    Users,
    Trophy,
} from '@lucide/vue';
import { computed } from 'vue';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import type { ProjectWithDetail as Project } from '@/types/projects';

const props = defineProps<{
    project: Project;
}>();

const stages = computed(() => {
    const allStages = [
        {
            id: 'imported',
            label: 'Em homologação',
            status: [
                'homologated',
                'review',
                'written_exam',
                'committee',
                'finished',
                'rejected',
            ].includes(props.project.stage)
                ? 'completed'
                : props.project.stage === 'imported'
                  ? 'current'
                  : 'pending',
            icon: CheckCircle,
            description: 'Projeto importado com sucesso.',
        },
        {
            id: 'homologated',
            label: 'Em distribuição',
            status: [
                'review',
                'written_exam',
                'committee',
                'finished',
                'rejected',
            ].includes(props.project.stage)
                ? 'completed'
                : props.project.stage === 'homologated'
                  ? 'current'
                  : 'pending',
            icon: CheckCircle,
            description: 'Projeto homologado.',
        },
        {
            id: 'review',
            label: 'Revisão',
            status: [
                'written_exam',
                'committee',
                'finished',
                'rejected',
            ].includes(props.project.stage)
                ? 'completed'
                : props.project.stage === 'review'
                  ? 'current'
                  : 'pending',
            icon: ClipboardCheck,
            description:
                props.project.is_evaluated &&
                props.project.review_assignments.data.length > 0
                    ? 'Avaliação concluída.'
                    : 'Aguardando avaliação dos pareceristas.',
        },
        {
            id: 'written_exam',
            label: 'Prova Escrita',
            status: ['committee', 'finished'].includes(props.project.stage)
                ? 'completed'
                : props.project.stage === 'written_exam'
                  ? 'current'
                  : 'pending',
            icon: PenTool,
            description: props.project.written_exam
                ? 'Nota da prova registrada.'
                : 'Aguardando realização da prova.',
            hidden: props.project.modality !== 'master',
        },
        {
            id: 'committee',
            label: 'Comitê',
            status: ['finished', 'rejected'].includes(props.project.stage)
                ? 'completed'
                : props.project.stage === 'committee'
                  ? 'current'
                  : 'pending',
            icon: Users,
            description: props.project.committee_evaluation
                ? 'Avaliação do comitê concluída.'
                : 'Aguardando avaliação do comitê.',
        },
        {
            id: 'finished',
            label: 'Finalizado',
            status:
                props.project.stage === 'finished'
                    ? 'completed'
                    : props.project.stage === 'rejected'
                      ? 'failed'
                      : 'pending',
            icon: props.project.stage === 'rejected' ? AlertCircle : Trophy,
            description:
                props.project.stage === 'finished'
                    ? 'Processo concluído.'
                    : props.project.stage === 'rejected'
                      ? 'Candidato não aprovado.'
                      : 'Aguardando conclusão.',
        },
    ];

    return allStages.filter((stage) => !stage.hidden);
});

const getStatusColor = (status: string) => {
    switch (status) {
        case 'completed':
            return 'text-green-500 bg-green-50 border-green-200';
        case 'current':
            return 'text-blue-500 bg-blue-50 border-blue-200 ring-2 ring-blue-100';
        case 'failed':
            return 'text-red-500 bg-red-50 border-red-200';
        default:
            return 'text-muted-foreground bg-muted/50 border-muted';
    }
};

const getStatusIconColor = (status: string) => {
    switch (status) {
        case 'completed':
            return 'text-green-600';
        case 'current':
            return 'text-blue-600';
        case 'failed':
            return 'text-red-600';
        default:
            return 'text-muted-foreground';
    }
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="text-lg">Progresso</CardTitle>
        </CardHeader>
        <CardContent class="p-0">
            <div class="flex flex-col">
                <div
                    v-for="(stage, index) in stages"
                    :key="stage.id"
                    class="relative px-6 py-4"
                >
                    <!-- Vertical line connecting stages -->
                    <div
                        v-if="index < stages.length - 1"
                        class="absolute top-14 bottom-0 left-9 w-0.5 bg-muted"
                    ></div>

                    <div class="flex items-start gap-4">
                        <div
                            :class="
                                cn(
                                    'z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full border text-xs transition-all',
                                    getStatusColor(stage.status),
                                )
                            "
                        >
                            <component
                                :is="stage.icon"
                                class="h-4 w-4"
                                :class="getStatusIconColor(stage.status)"
                            />
                        </div>
                        <div class="space-y-1">
                            <p
                                :class="
                                    cn(
                                        'text-sm leading-none font-medium',
                                        stage.status === 'pending'
                                            ? 'text-muted-foreground'
                                            : 'text-foreground',
                                    )
                                "
                            >
                                {{ stage.label }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ stage.description }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
