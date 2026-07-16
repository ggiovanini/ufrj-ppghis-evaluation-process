<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Pencil } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import team from '@/routes/team';

interface User {
    id: number;
    name: string;
    email: string;
    roles: { name: string; label: string }[];
}

defineProps<{
    user: {
        data: User;
    };
    stats: {
        to_evaluate: number;
        evaluated: number;
        written_exams: number;
        committee_evaluations: number;
    };
}>();
</script>

<template>
    <Head :title="user.data.name" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Button variant="outline" size="icon" as-child>
                    <Link :href="team.index().url">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">{{ user.data.name }}</h1>
                    <p class="text-muted-foreground">{{ user.data.email }}</p>
                </div>
            </div>
            <Button as-child>
                <Link :href="team.edit(user.data.id).url">
                    <Pencil class="mr-1 h-4 w-4" />
                    Editar
                </Link>
            </Button>
        </div>

        <div class="flex flex-wrap gap-2">
            <Badge v-for="role in user.data.roles" :key="role.name" variant="secondary">
                {{ role.label }}
            </Badge>
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium uppercase text-muted-foreground">Projetos para Avaliar</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.to_evaluate }}</div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium uppercase text-muted-foreground">Projetos Avaliados</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.evaluated }}</div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium uppercase text-muted-foreground">Provas Escritas</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.written_exams }}</div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium uppercase text-muted-foreground">Avaliações de Comitê</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.committee_evaluations }}</div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
