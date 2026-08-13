<script setup lang="ts">
import { Check } from '@lucide/vue';
import { cn } from '@/lib/utils';

interface Phase {
    name: string;
    value: string;
    label: string;
}

const props = defineProps<{
    phases: Phase[];
    currentPhase: string;
}>();

const getPhaseStatus = (phaseValue: string) => {
    const phaseIndex = props.phases.findIndex((p) => p.value === phaseValue);
    const currentIndex = props.phases.findIndex(
        (p) => p.value === props.currentPhase,
    );

    if (phaseIndex < currentIndex) {
        return 'completed';
    }

    if (phaseIndex === currentIndex) {
        return 'current';
    }

    return 'pending';
};
</script>

<template>
    <div class="relative flex w-full items-center justify-between py-8">
        <!-- Connecting Line -->
        <div
            class="absolute top-1/2 left-0 h-0.5 w-full -translate-y-1/2 bg-muted"
        >
            <div
                class="h-full bg-primary transition-all duration-500"
                :style="{
                    width: `${(phases.findIndex((p) => p.value === currentPhase) / (phases.length - 1)) * 100}%`,
                }"
            />
        </div>

        <!-- Steps -->
        <div
            v-for="phase in phases"
            :key="phase.value"
            class="relative z-10 flex flex-col items-center"
        >
            <div
                :class="
                    cn(
                        'flex h-10 w-10 items-center justify-center rounded-full border-2 bg-background transition-colors duration-200',
                        getPhaseStatus(phase.value) === 'completed' &&
                            'border-primary bg-primary text-primary-foreground',
                        getPhaseStatus(phase.value) === 'current' &&
                            'border-primary ring-4 ring-primary/20',
                        getPhaseStatus(phase.value) === 'pending' &&
                            'border-muted text-muted-foreground',
                    )
                "
            >
                <Check
                    v-if="getPhaseStatus(phase.value) === 'completed'"
                    class="h-6 w-6"
                />
                <span v-else class="text-sm font-semibold">
                    {{ phases.indexOf(phase) + 1 }}
                </span>
            </div>
            <div class="absolute top-9 mt-2 w-max text-center">
                <span
                    :class="
                        cn(
                            'text-xs font-medium tracking-wider uppercase transition-colors duration-200',
                            getPhaseStatus(phase.value) === 'current'
                                ? 'text-primary'
                                : 'text-muted-foreground',
                        )
                    "
                >
                    {{ phase.label }}
                </span>
            </div>
        </div>
    </div>
</template>
