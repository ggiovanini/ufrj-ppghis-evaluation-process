<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { LogOut } from '@lucide/vue';

const page = usePage();
</script>

<template>
    <div
        v-if="page.props.auth.is_impersonating"
        class="flex items-center justify-between border-b border-yellow-200 bg-yellow-50 px-4 py-2 text-yellow-800 dark:border-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200"
    >
        <div class="flex items-center gap-2 text-sm font-medium">
            <span
                class="flex h-2 w-2 animate-pulse rounded-full bg-yellow-400"
            ></span>
            Você está acessando como
            <strong>{{ page.props.auth.user.name }}</strong>
        </div>

        <Link
            href="/impersonate/leave"
            method="post"
            as="button"
            class="flex items-center gap-1 text-sm font-semibold hover:underline"
        >
            <LogOut class="h-4 w-4" />
            Sair da Personificação
        </Link>
    </div>
</template>
