<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';
import { Button } from '@/components/ui/button';

type Props = {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
    icon?: LucideIcon;
    url?: string;
    back?: boolean;
};

withDefaults(defineProps<Props>(), {
    variant: 'default',
});

function goBack() {
    window.history.back();
}
</script>

<template>
    <header
        :class="
            variant === 'small'
                ? 'flex flex-row items-center justify-start gap-2'
                : 'mb-8 flex flex-row items-center justify-start gap-2 space-y-0.5'
        "
    >
        <Button
            @click="goBack"
            class="cursor-pointer"
            v-if="back" variant="ghost" size="icon-lg" as-child>
            <component :is="icon" class="h-9 w-9" />
        </Button>
        <Button v-else-if="!back && url && icon" variant="ghost" size="icon-lg" as-child>
            <Link :href="url">
                <component :is="icon" class="h-9 w-9" />
            </Link>
        </Button>
        <component v-else-if="icon" :is="icon" class="h-9 w-9" />
        <div class="flex flex-col pe-20 items-start space-y-0.5">
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
        <slot />
    </header>
</template>
