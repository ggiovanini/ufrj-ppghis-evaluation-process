<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ChevronLeft, Pencil, User as UserIcon } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import ProjectListOnlyShow from '@/components/selection/ProjectListOnlyShow.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import team from '@/routes/team';
import { authCan } from '@/types';
import type { DataFilters, DataPagination, Resource } from '@/types/pagination';
import type { Project } from '@/types/projects';
import type { Reviewer } from '@/types/reviewer';
import type {
    SelectionProcess,
    SelectionProcessStats,
} from '@/types/selection-process';

interface User {
    id: number;
    name: string;
    email: string;
    roles: { name: string; label: string }[];
    filters: DataFilters;
}

const props = defineProps<{
    user: Resource<User>;
    stats: SelectionProcessStats;
    reviewers: Reviewer[];
    selection: Resource<SelectionProcess>;
    projects: DataPagination<Project>;
    filters: DataFilters;
}>();

const page = usePage();

const canImpersonate = computed(() => {
    return (
        page.props.auth.user.id !== props.user.data.id &&
        authCan(page.props.auth, 'users.manage')
    );
});
</script>

<template>
    <Head :title="user.data.name" />
    <Heading
        variant="small"
        :title="user.data.name"
        :description="user.data.email"
        :icon="ChevronLeft"
        :back="true"
    >
        <div class="flex flex-1 flex-row items-center justify-end gap-2">
            <Button v-if="canImpersonate" as-child variant="outline">
                <Link
                    :href="team.impersonate(user.data.id).url"
                    method="post"
                    as="button"
                >
                    <UserIcon class="mr-1 h-4 w-4" />
                    Personificar
                </Link>
            </Button>

            <Button as-child>
                <Link :href="team.edit(user.data.id).url">
                    <Pencil class="mr-1 h-4 w-4" />
                    Editar
                </Link>
            </Button>
        </div>
    </Heading>

    <div class="space-y-6">
        <div class="flex flex-wrap gap-2">
            <Badge
                v-for="role in user.data.roles"
                :key="role.name"
                variant="secondary"
            >
                {{ role.label }}
            </Badge>
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle
                        class="text-sm font-medium text-muted-foreground uppercase"
                        >Projetos para Avaliar</CardTitle
                    >
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{ stats.total_reviews }}
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle
                        class="text-sm font-medium text-muted-foreground uppercase"
                        >Projetos Avaliados</CardTitle
                    >
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{ stats.total_reviewed }}
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle
                        class="text-sm font-medium text-muted-foreground uppercase"
                        >Provas Escritas</CardTitle
                    >
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{ stats.written_exams }}
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle
                        class="text-sm font-medium text-muted-foreground uppercase"
                        >Avaliações de Comitê</CardTitle
                    >
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{ stats.committee_evaluations }}
                    </div>
                </CardContent>
            </Card>
        </div>

        <Heading
            title="Lista de projetos"
            description="Essa lista contém todos os projetos que o usuário está envolvido"
            class="mt-6"
        />
        <ProjectListOnlyShow
            :selection="selection.data"
            :projects="projects"
            :reviewers="reviewers"
            :filters="filters"
            :stats="stats"
            :user-id="user.data.id"
        />
    </div>
</template>
