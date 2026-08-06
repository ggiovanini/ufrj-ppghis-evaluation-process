<script setup lang="ts">
import { Layers2Icon } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import SelectionTimeline from '@/components/SelectionTimeline.vue';

interface Selection {
    id: number;
    name: string;
    description: string;
    year: number;
    phase: string;
}

defineProps<{
    selection?: {
        data: Selection;
    };
    phases: {
        name: string;
        value: string;
        label: string;
    }[];
}>();
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 pt-8">
        <Heading
            v-if="selection"
            :title="selection.data.name"
            :description="
                selection.data.description || 'Informações do processo seletivo'
            "
            :icon="Layers2Icon"
        >
            <div class="flex-1 px-12">
                <SelectionTimeline
                    :phases="phases"
                    :current-phase="selection.data.phase"
                    class="z-10"
                />
            </div>
        </Heading>
        <slot />
    </div>
</template>
