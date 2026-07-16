<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';

defineProps<{
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
        path: string;
        per_page: number;
        to: number;
        total: number;
    };
}>();
</script>

<template>
    <nav
        v-if="meta.links.length > 3"
        class="mt-2 flex items-center justify-between space-x-1 p-2"
        aria-label="Paginação"
    >
        <div class="flex items-center justify-center space-x-1 text-sm">
            Exibindo {{ meta.from }} até {{ meta.to }} de
            {{ meta.total }} registros.
        </div>
        <div class="flex items-end justify-center space-x-1">
            <template v-for="(link, key) in meta.links" :key="key">
                <div
                    v-if="link.url === null"
                    class="inline-flex h-10 items-center justify-center rounded-xl aspect-square border border-input bg-background p-2 text-sm font-medium text-muted-foreground opacity-50 ring-offset-background transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                >
                    <template v-if="link.label.includes('terior')">
                        <ChevronLeft class="mr-1 h-4 w-4" />
                    </template>
                    <template v-else-if="link.label.includes('ximo')">
                        <ChevronRight class="mr-1 h-4 w-4" />
                    </template>
                    <template v-else>
                        {{ link.label }}
                    </template>
                </div>
                <Link
                    v-else
                    :href="link.url"
                    class="inline-flex h-10 items-center justify-center rounded-xl aspect-square border border-input bg-background p-2 text-sm font-medium ring-offset-background transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                    :class="{
                        'bg-primary text-primary-foreground hover:bg-primary/90 hover:text-primary-foreground':
                            link.active,
                    }"
                >
                    <template v-if="link.label.includes('terior')">
                        <ChevronLeft class="mr-1 h-4 w-4" />
                    </template>
                    <template v-else-if="link.label.includes('ximo')">
                        <ChevronRight class="mr-1 h-4 w-4" />
                    </template>
                    <template v-else>
                        {{ link.label }}
                    </template>
                </Link>
            </template>
        </div>
    </nav>
</template>
