<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Edit, PenTool } from '@lucide/vue';
import { ref } from 'vue';
import CommitteeScoreModal from '@/components/selection/CommitteeScoreModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { authCan } from '@/types';
import type { Project, ProjectWithDetail } from '@/types/projects';

const props = defineProps<{
    project: Project | ProjectWithDetail;
    selectionId: number;
    selectionPhase: string;
}>();

const page = usePage();
const auth = page.props.auth;
const canManage = authCan(auth, 'projects.manage');
const canEvaluateCommittee = authCan(auth, 'committee.evaluate');
const canEditScore = canManage || canEvaluateCommittee;

const isScoreModalOpen = ref(false);

const editScore = () => {
    isScoreModalOpen.value = true;
};
</script>

<template>
    <Card
        v-if="canEditScore && ['committee', 'finished'].includes(project.stage)"
    >
        <CardHeader
            class="flex flex-row items-center justify-between space-y-0 pb-2"
        >
            <CardTitle class="flex flex-row items-center gap-2 text-base">
                <PenTool class="h-4 w-4" />
                Avaliação do comitê
            </CardTitle>
            <Button
                v-if="
                    project.stage === 'committee' &&
                    props.selectionPhase === 'COMMITTEE' &&
                    canEditScore
                "
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                @click="editScore"
            >
                <Edit class="h-4 w-4" />
            </Button>
        </CardHeader>
        <CardContent>
            <div class="flex items-center justify-between">
                <span class="text-sm">Nota</span>
                <Badge variant="secondary" class="text-lg">
                    {{ project.committee_score_label || '---' }}
                </Badge>
            </div>
            <div
                v-if="
                    !project.committee_score &&
                    project.stage === 'committee' &&
                    props.selectionPhase === 'COMMITTEE' &&
                    canEditScore
                "
                class="mt-4"
            >
                <Button variant="outline" class="w-full" @click="editScore">
                    Inserir Nota
                </Button>
            </div>
        </CardContent>
    </Card>

    <CommitteeScoreModal
        v-model:open="isScoreModalOpen"
        :selection-id="selectionId"
        :project="project"
    />
</template>

<style scoped></style>
