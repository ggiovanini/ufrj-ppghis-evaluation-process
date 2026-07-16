<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { PlusCircle, List, ListFilter, UserCogIcon } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { create, index, role } from '@/routes/team';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Criar novo',
        href: create(),
        icon: PlusCircle,
    },
    {
        title: 'Todos',
        href: index(),
        icon: List,
    },
    {
        title: 'Administradores',
        href: role({ role: 'admin' }),
        icon: ListFilter,
    },
    {
        title: 'Avaliadores',
        href: role({ role: 'reviewer' }),
        icon: ListFilter,
    },
    {
        title: 'Banca de Mestrado',
        href: role({ role: 'master_committee' }),
        icon: ListFilter,
    },
    {
        title: 'Banca de Doutorado',
        href: role({ role: 'doctorate_committee' }),
        icon: ListFilter,
    },
];

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <div class="px-4 py-6">
        <Heading title="Equipe" description="Gerenciar membros da equipe" :icon="UserCogIcon" />

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav
                    class="flex flex-col space-y-1 space-x-0"
                    aria-label="Settings"
                >
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'w-full justify-start',
                            { 'bg-muted': isCurrentUrl(item.href) },
                        ]"
                        as-child
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1">
                <section class="w-full space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
