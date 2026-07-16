<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';

type Props = {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
    icon?: LucideIcon;
};

withDefaults(defineProps<Props>(), {
    variant: 'default',
});
</script>

<template>
    <header :class="variant === 'small' ? '' : 'mb-8 gap-2 flex flex-row items-center justify-between space-y-0.5'">
        <component v-if="icon" :is="icon" class="h-9 w-9" />
        <div class="flex flex-col items-start space-y-0.5 flex-1">
            <h2
                :class="
                    variant === 'small'
                        ? 'mb-0.5 text-base font-medium'
                        : 'text-xl font-semibold tracking-tight'
                "
            >
                {{ title }}
            </h2>
            <p v-if="description" class="text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>
    </header>
</template>
