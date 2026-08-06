<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ClipboardCheck, LucideCheckCircle, View } from '@lucide/vue';
import { computed, ref, onMounted } from 'vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { authCan } from '@/types';
import type { ProjectWithDetail } from '@/types/projects';
import type { ReviewAssignment, ReviewScoreOptions } from '@/types/reviewer';
import ProjectReviewForm from './ProjectReviewForm.vue';
import ProjectReviewView from './ProjectReviewView.vue';

const page = usePage();
const auth = page.props.auth;

const props = defineProps<{
    project: ProjectWithDetail;
    reviewScoreOptions: ReviewScoreOptions;
}>();

const currentAssignment = computed(() =>
    props.project.review_assignments.data.find(
        (a) => a.user.id === auth.user.id,
    ),
);

const isSubmitted = computed(
    () => currentAssignment.value?.review?.status === 'submitted',
);

const isOpen = ref(false);
const isViewOpen = ref(false);
const selectedAssignment = ref<ReviewAssignment | null>(null);
const isMounted = ref(false);

onMounted(() => {
    isMounted.value = true;
});

const openReviewModal = () => {
    isOpen.value = true;
};

const openViewReviewModal = (assignment: ReviewAssignment) => {
    selectedAssignment.value = assignment;
    isViewOpen.value = true;
};
</script>

<template>
    <Card
        class="relative flex"
        v-if="isMounted && project.review_assignments.data.length"
    >
        <PlaceholderPattern class="z-0" />
        <CardHeader>
            <CardTitle class="flex flex-row items-center gap-2 text-base">
                <ClipboardCheck class="h-4 w-4" />
                Pareceres
            </CardTitle>
        </CardHeader>
        <CardContent class="z-10 grid grid-cols-1 space-y-2">
            <div
                v-if="project.review_score"
                class="flex w-full items-center justify-between"
            >
                <span class="text-sm">Nota média da avaliação</span>
                <Badge variant="secondary" class="text-lg">
                    {{ project.review_score_label || '---' }}
                </Badge>
            </div>
            <template
                v-for="assignment in project.review_assignments.data"
                :key="assignment.id"
            >
                <div
                    v-if="
                        authCan(auth, 'users.manage') ||
                        assignment.user.id === auth.user.id
                    "
                    class="rounded-lg border border-muted bg-card px-4 pt-3 pb-4 text-sm"
                    :class="
                        assignment.review?.status === 'submitted'
                            ? 'outline-2 outline-foreground/20'
                            : ''
                    "
                >
                    <div
                        class="flex w-full flex-row items-center justify-between overflow-hidden"
                    >
                        <div
                            class="flex w-full flex-1 flex-col overflow-hidden"
                        >
                            <p class="text-sm">Avaliador</p>
                            <div class="truncate font-bold ...">
                                {{ assignment.user.name }}
                            </div>
                            <Badge
                                size="sm"
                                class="mt-1"
                                v-if="assignment.chosen_by_candidate"
                            >
                                Escolha do candidato
                            </Badge>
                        </div>
                        <Button
                            v-if="assignment.review?.status === 'submitted'"
                            class="ml-2"
                            variant="ghost"
                            @click="openViewReviewModal(assignment)"
                        >
                            <View class="h-4 w-4" />
                        </Button>
                    </div>
                    <Separator class="my-2" />
                    <template v-if="assignment.review">
                        <p class="whitespace-nowrap">
                            Status: {{ assignment.review?.status_label }}
                        </p>
                        <Separator class="my-2" />
                        <p class="whitespace-nowrap">
                            Nota: {{ assignment.review?.score_label }}
                        </p>
                        <Separator class="my-2" />
                        <p>
                            Justificativa:
                            <span
                                v-if="assignment.review.comments"
                                class="text-muted-foreground italic"
                            >
                                "{{ assignment.review.comments }}"
                            </span>
                        </p>
                    </template>
                    <p v-else class="whitespace-nowrap">
                        Status:
                        <Badge variant="outline">Aguardando</Badge>
                    </p>
                </div>
            </template>
        </CardContent>
        <template v-if="!authCan(auth, 'users.manage')">
            <CardFooter class="z-10" v-if="currentAssignment && !isSubmitted">
                <Button
                    class="w-full"
                    variant="default"
                    @click="openReviewModal"
                >
                    Avaliar
                </Button>

                <ProjectReviewForm
                    v-model:open="isOpen"
                    :project="project"
                    :reviewScoreOptions="reviewScoreOptions"
                />
            </CardFooter>
            <CardFooter class="z-10" v-else>
                <Button class="w-full" variant="secondary" disabled>
                    <LucideCheckCircle class="h-4 w-4" /> Avaliação enviada
                </Button>
            </CardFooter>
        </template>

        <ProjectReviewView
            v-model:open="isViewOpen"
            :assignment="selectedAssignment"
            :project="project"
        />
    </Card>
</template>

<style scoped></style>
