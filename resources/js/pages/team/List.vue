<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Layout from '@/layouts/team/Layout.vue';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Administradores',
        href: '#admin',
    },
    {
        title: 'Avaliadores',
        href: '#avaliadores',
    },
    {
        title: 'Banca',
        href: '#banca',
    },
];
</script>

<template>
    <Layout
        title="Equipe"
        description="Gerenciar membros da equipe"
        :sidebarNavItems="sidebarNavItems"
    >
        <Head title="List" />
        <h1>Team</h1>
    </Layout>
</template>
