<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Edit, PenTool } from '@lucide/vue';
import { ref } from 'vue';
import WrittenExamScoreModal from '@/components/selection/WrittenExamScoreModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { authCan } from '@/types';
import type { Project, ProjectWithDetail } from '@/types/projects';

defineProps<{
    project: Project | ProjectWithDetail;
    selectionId: number;
}>();

const page = usePage();
const auth = page.props.auth;
const canManage = authCan(auth, 'projects.manage');

const isScoreModalOpen = ref(false);

const editScore = () => {
    isScoreModalOpen.value = true;
};
</script>

<template>
    <Card v-if="project.modality === 'master' && canManage && ['written_exam', 'committee', 'finished'].includes(project.stage)">
        <CardHeader
            class="flex flex-row items-center justify-between space-y-0 pb-2"
        >
            <CardTitle class="flex flex-row items-center gap-2 text-base">
                <PenTool class="h-4 w-4" />
                Prova escrita
            </CardTitle>
            <Button
                v-if="project.stage === 'written_exam'"
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
                <span class="text-sm">Nota da prova</span>
                <Badge variant="secondary" class="text-lg">
                    {{ project.written_exam_score_label || '---' }}
                </Badge>
            </div>
            <div v-if="!project.written_exam_score && canManage" class="mt-4">
                <Button variant="outline" class="w-full" @click="editScore">
                    Inserir Nota
                </Button>
            </div>
        </CardContent>
    </Card>

    <WrittenExamScoreModal
        v-model:open="isScoreModalOpen"
        :selection-id="selectionId"
        :project="project"
    />
</template>

<style scoped></style>
