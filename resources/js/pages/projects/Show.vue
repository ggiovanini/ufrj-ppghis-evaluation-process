<script setup lang="ts">
import { Head, router, setLayoutProps, usePage } from '@inertiajs/vue3';
import { Check, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import ProjectCommittee from '@/pages/projects/partials/ProjectCommittee.vue';
import ProjectDataResume from '@/pages/projects/partials/ProjectDataResume.vue';
import ProjectReviewers from '@/pages/projects/partials/ProjectReviewers.vue';
import ProjectWrittenExam from '@/pages/projects/partials/ProjectWrittenExam.vue';
import { dashboard } from '@/routes';
import routeSelection from '@/routes/selection';
import { authCan } from '@/types';
import type { ProjectWithDetail as Project } from '@/types/projects';
import type { ReviewScoreOptions } from '@/types/reviewer';
import type { SelectionProcess } from '@/types/selection-process';
import type { SelectionProcessPhaseObject } from '@/types/selection-process';
import ProjectData from './partials/ProjectData.vue';
import ProjectProgress from './partials/ProjectProgress.vue';

const props = defineProps<{
    selection: {
        data: SelectionProcess;
    };
    project: Project;
    reviewScoreOptions: ReviewScoreOptions;
    phases: SelectionProcessPhaseObject[];
}>();

const page = usePage();
const auth = page.props.auth;

const canHomologate = computed(
    () =>
        props.selection.data.phase === 'HOMOLOGATION' &&
        authCan(auth, 'projects.manage'),
);

const isConfirmingHomologation = ref(false);
const homologationStatus = ref<'approved' | 'rejected'>('approved');
const homologationReason = ref('');
const homologationReasonError = ref('');

const requestHomologation = (status: 'approved' | 'rejected') => {
    homologationStatus.value = status;
    homologationReason.value = '';
    homologationReasonError.value = '';
    isConfirmingHomologation.value = true;
};

const confirmHomologation = () => {
    if (
        homologationStatus.value === 'rejected' &&
        homologationReason.value.trim() === ''
    ) {
        homologationReasonError.value = 'Informe o motivo da desaprovação.';

        return;
    }

    router.patch(
        routeSelection.projects.homologation.update({
            selection: props.selection.data.id,
            project: props.project.id,
        }).url,
        {
            status: homologationStatus.value,
            reason:
                homologationStatus.value === 'rejected'
                    ? homologationReason.value.trim()
                    : null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isConfirmingHomologation.value = false;
            },
        },
    );
};

const breadcrumbs = computed(() => {
    const result = [
        {
            title: 'Painel',
            href: dashboard().url,
        },
    ];

    if (authCan(auth, 'review.view-own')) {
        result.push({
            title: props.selection.data.name,
            href: routeSelection.evaluate(props.selection.data.id).url,
        });
    }

    if (authCan(auth, 'committee.evaluate')) {
        result.push({
            title: props.selection.data.name,
            href: routeSelection.committee(props.selection.data.id).url,
        });
    }

    if (authCan(auth, 'projects.view')) {
        result.push({
            title: props.selection.data.name,
            href: routeSelection.show(props.selection.data.id).url,
        });
    }

    result.push({
        title: 'Detalhes do projeto',
        href: '#',
    });

    return result;
});

setLayoutProps({
    breadcrumbs: breadcrumbs,
    phases: props.phases,
});
</script>

<template>
    <Head :title="`Projeto - ${project.candidate_name}`" />

    <div class="container mx-auto py-6">
        <div class="flex flex-col gap-8 lg:flex-row">
            <!-- Main Content -->
            <div class="flex-1 space-y-6">
                <Heading
                    :title="project.candidate_name"
                    :description="project.title"
                >
                    <div class="flex flex-1 justify-end">
                        <Badge
                            :variant="
                                project.stage === 'rejected'
                                    ? 'destructive'
                                    : 'default'
                            "
                        >
                            {{ project.stage_label.toUpperCase() }}
                        </Badge>
                    </div>
                </Heading>

                <div
                    v-if="canHomologate"
                    class="flex flex-wrap items-center justify-between gap-4 rounded-lg border bg-muted/30 p-4"
                >
                    <div>
                        <p class="font-semibold">Homologação do cadastro</p>
                        <p class="text-sm text-muted-foreground">
                            Confira os dados e defina se o cadastro seguirá para
                            a distribuição.
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <Button
                            :variant="
                                project.homologation_status === 'approved'
                                    ? 'default'
                                    : 'outline'
                            "
                            @click="requestHomologation('approved')"
                        >
                            <Check class="h-4 w-4" />
                            Aprovar cadastro
                        </Button>
                        <Button
                            :variant="
                                project.homologation_status === 'rejected'
                                    ? 'destructive'
                                    : 'outline'
                            "
                            @click="requestHomologation('rejected')"
                        >
                            <X class="h-4 w-4" />
                            Desaprovar cadastro
                        </Button>
                    </div>
                </div>

                <div
                    v-if="
                        canHomologate &&
                        project.homologation_status === 'rejected' &&
                        project.homologation_reason
                    "
                    class="rounded-lg border border-destructive/30 bg-destructive/5 p-4"
                >
                    <p class="text-sm font-semibold text-destructive">
                        Motivo da desaprovação
                    </p>
                    <p class="mt-1 text-sm whitespace-pre-wrap">
                        {{ project.homologation_reason }}
                    </p>
                </div>

                <Card class="pb-0 outline-4 outline-foreground/20">
                    <CardHeader>
                        <CardTitle class="text-lg">
                            <div
                                class="flex w-full flex-row items-center justify-between"
                            >
                                <div>
                                    Dados do projeto #{{ project.register_id }}
                                </div>
                                <div
                                    class="rounded-lg bg-muted-foreground px-4 py-1 font-bold text-background uppercase"
                                >
                                    {{ project.modality_label }}
                                </div>
                            </div>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="space-y-4">
                            <div>
                                <p
                                    class="text-sm font-medium text-muted-foreground"
                                >
                                    Título
                                </p>
                                <h1 class="text-4xl">{{ project.title }}</h1>
                                <p class="mt-4 text-base">
                                    {{ project.description }}
                                </p>
                            </div>
                            <div v-if="authCan(auth, 'projects.manage')">
                                <p
                                    class="text-sm font-medium text-muted-foreground"
                                >
                                    Avaliador indicado
                                </p>
                                <p class="text-base">
                                    {{ project.indication }}
                                </p>
                            </div>
                        </div>

                        <div class="relative -mx-6 flex p-6">
                            <PlaceholderPattern class="z-0" />
                            <ProjectData
                                v-if="authCan(auth, 'projects.manage')"
                                :project="project"
                            />
                            <ProjectDataResume v-else :project="project" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Dialog v-model:open="isConfirmingHomologation">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {{
                                homologationStatus === 'rejected'
                                    ? 'Desaprovar cadastro'
                                    : 'Aprovar cadastro'
                            }}
                        </DialogTitle>
                        <DialogDescription>
                            Confirma esta decisão para o cadastro de
                            <strong>{{ project.candidate_name }}</strong
                            >?
                        </DialogDescription>
                    </DialogHeader>

                    <div
                        v-if="homologationStatus === 'rejected'"
                        class="space-y-2"
                    >
                        <Label for="show-homologation-reason">
                            Motivo da desaprovação
                        </Label>
                        <textarea
                            id="show-homologation-reason"
                            v-model="homologationReason"
                            rows="4"
                            placeholder="Informe o motivo..."
                            class="flex min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        />
                        <p
                            v-if="homologationReasonError"
                            class="text-sm text-destructive"
                        >
                            {{ homologationReasonError }}
                        </p>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            @click="isConfirmingHomologation = false"
                        >
                            Cancelar
                        </Button>
                        <Button
                            :variant="
                                homologationStatus === 'rejected'
                                    ? 'destructive'
                                    : 'default'
                            "
                            @click="confirmHomologation"
                        >
                            Confirmar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Sidebar -->
            <div class="w-full space-y-6 lg:w-80">
                <ProjectProgress :project="project" />

                <ProjectReviewers
                    :project="project"
                    :review-score-options="reviewScoreOptions"
                />

                <ProjectWrittenExam
                    :project="project"
                    :selection-id="selection.data.id"
                />

                <ProjectCommittee
                    :project="project"
                    :selection-id="selection.data.id"
                    :selection-phase="selection.data.phase"
                />

                <Card
                    v-if="
                        ['RESULTS', 'FINISHED'].includes(selection.data.phase)
                    "
                >
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base">Resultado final</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center justify-between">
                            <span class="text-sm">Nota final</span>
                            <Badge variant="secondary" class="text-lg">
                                {{ project.final_score_label || '---' }}
                            </Badge>
                        </div>
                        <Badge
                            v-if="project.final_score !== null"
                            class="mt-3"
                            :class="
                                project.final_score_passes
                                    ? 'bg-green-600 text-white'
                                    : 'bg-red-600 text-white'
                            "
                        >
                            {{
                                project.final_score_passes
                                    ? 'Aprovado'
                                    : 'Reprovado'
                            }}
                        </Badge>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
