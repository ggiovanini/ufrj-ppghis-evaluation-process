<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
import { home } from '@/routes';
import imageBgAuth from '@/assets/images/bg-auth.jpg';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0"
    >
        <div
            class="relative hidden h-full flex-col items-center justify-center bg-muted p-10 text-white lg:flex dark:border-r"
        >
            <div
                class="absolute inset-0 bg-cover bg-center"
                :style="{
                    backgroundImage: `url(${imageBgAuth})`,
                }"
            />
            <Link
                :href="home()"
                class="relative z-20 flex items-center text-lg font-medium"
            >
                <AppLogo class-name="mr-2 h-20 fill-current" />
            </Link>
        </div>
        <div class="lg:p-8">
            <div
                class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-87.5"
            >
                <div class="flex flex-col items-center space-y-2 text-center">
                    <Link
                        :href="home()"
                        class="relative z-20 items-center text-lg font-medium mb-9 lg:hidden flex"
                    >
                        <AppLogo class-name="mr-2 h-20 fill-current" />
                    </Link>
                    <h1 class="text-xl font-medium tracking-tight" v-if="title">
                        {{ title }}
                    </h1>
                    <p class="text-sm text-muted-foreground" v-if="description">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
