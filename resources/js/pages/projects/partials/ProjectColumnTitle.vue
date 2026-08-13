<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import type { Project } from '@/types/projects';

defineProps<{
    project: Project
}>();
</script>

<template>
    <div class="flex flex-col gap-2">
        <div class="flex flex-row gap-1">
            <Badge>#{{ project.register_id }}</Badge>
            <Badge variant="secondary">
                {{ project.modality_label }}
            </Badge>
        </div>
        <div class="flex flex-col">
            <p>{{ project.candidate_name }}</p>
            <p class="line-clamp-1 text-xs font-normal text-muted-foreground">
                {{ project.title }}
            </p>
        </div>
    </div>
</template>
