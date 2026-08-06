<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { LogOut } from '@lucide/vue';

const page = usePage();
</script>

<template>
    <div 
        v-if="page.props.auth.is_impersonating" 
        class="bg-yellow-50 dark:bg-yellow-900/20 border-b border-yellow-200 dark:border-yellow-800 px-4 py-2 flex items-center justify-between text-yellow-800 dark:text-yellow-200"
    >
        <div class="flex items-center gap-2 text-sm font-medium">
            <span class="flex h-2 w-2 rounded-full bg-yellow-400 animate-pulse"></span>
            Você está acessando como <strong>{{ page.props.auth.user.name }}</strong>
        </div>
        
        <Link 
            href="/impersonate/leave" 
            method="post" 
            as="button"
            class="text-sm font-semibold hover:underline flex items-center gap-1"
        >
            <LogOut class="h-4 w-4" />
            Sair da Personificação
        </Link>
    </div>
</template>
