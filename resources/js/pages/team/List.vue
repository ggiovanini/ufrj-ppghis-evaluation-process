<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    MoreHorizontal,
    Copy,
    Pencil,
    TrashIcon,
    Search,
    ArrowUpDown,
    ArrowUp,
    ArrowDown,
    X,
    PlusCircle,
    Asterisk,
} from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { ref } from 'vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import team from '@/routes/team';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface User {
    id: number;
    name: string;
    email: string;
    roles: { name: string; label: string }[];
}

interface DataPagination {
    data: User[];
    links: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        links: PaginationLink[];
        path: string;
        per_page: number;
        to: number;
        total: number;
    };
}

const props = defineProps<{
    users: DataPagination;
    currentRole?: string;
    filters?: {
        search?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
    };
}>();

const isConfirmingDeletion = ref(false);
const userToDelete = ref<User | null>(null);
const search = ref(props.filters?.search || '');

watchDebounced(
    search,
    (value) => {
        router.get(
            window.location.pathname,
            {
                ...props.filters,
                search: value,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    },
    { debounce: 300 },
);

const sortBy = (column: string) => {
    let direction: 'asc' | 'desc' = 'asc';
    if (props.filters?.sort === column && props.filters?.direction === 'asc') {
        direction = 'desc';
    }

    router.get(
        window.location.pathname,
        {
            ...props.filters,
            sort: column,
            direction: direction,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const clearSearch = () => {
    search.value = '';
};

const copyEmail = (email: string) => {
    navigator.clipboard.writeText(email);
};

const navigateToShow = (userId: number) => {
    router.visit(team.show(userId).url);
};

const confirmDeletion = (user: User) => {
    userToDelete.value = user;
    isConfirmingDeletion.value = true;
};

const deleteUser = () => {
    if (userToDelete.value) {
        router.delete(team.delete(userToDelete.value.id).url, {
            onSuccess: () => {
                isConfirmingDeletion.value = false;
                userToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <Head title="Membros da equipe" />

    <div class="space-y-4">
        <div
            class="relative w-full overflow-hidden rounded-xl border bg-foreground/5 p-2"
        >
            <div class="flex items-center justify-between gap-4 p-2 mb-2">
                <div class="relative w-full max-w-sm">
                    <Search
                        class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        placeholder="Filtrar..."
                        class="pl-10"
                    />
                    <button
                        v-if="search"
                        @click="clearSearch"
                        class="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <Button as-child>
                        <Link :href="team.create().url">
                            <PlusCircle class="h-4 w-4" />
                            Novo
                        </Link>
                    </Button>
                </div>
            </div>

            <div
                class="w-full overflow-hidden rounded-md border bg-card outline outline-foreground/30"
            >
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead
                                class="cursor-pointer hover:bg-muted/50"
                                @click="sortBy('name')"
                            >
                                <div class="flex items-center gap-2 ps-2">
                                    Nome
                                    <template v-if="filters?.sort === 'name'">
                                        <ArrowUp
                                            v-if="filters?.direction === 'asc'"
                                            class="h-4 w-4"
                                        />
                                        <ArrowDown v-else class="h-4 w-4" />
                                    </template>
                                    <ArrowUpDown
                                        v-else
                                        class="h-4 w-4 text-muted-foreground/50"
                                    />
                                </div>
                            </TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-muted/50"
                                @click="sortBy('email')"
                            >
                                <div class="flex items-center gap-2">
                                    E-mail
                                    <template v-if="filters?.sort === 'email'">
                                        <ArrowUp
                                            v-if="filters?.direction === 'asc'"
                                            class="h-4 w-4"
                                        />
                                        <ArrowDown v-else class="h-4 w-4" />
                                    </template>
                                    <ArrowUpDown
                                        v-else
                                        class="h-4 w-4 text-muted-foreground/50"
                                    />
                                </div>
                            </TableHead>
                            <TableHead>Papéis</TableHead>
                            <TableHead class="flex-1 flex items-center justify-end pe-4">
                                <Asterisk class="w-4 h-4"/>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="user in users.data"
                            :key="user.id"
                            class="cursor-pointer"
                            @click="navigateToShow(user.id)"
                        >
                            <TableCell class="font-medium whitespace-nowrap ps-4">
                                {{ user.name }}
                            </TableCell>
                            <TableCell>{{ user.email }}</TableCell>
                            <TableCell>
                                <div class="flex flex-wrap gap-1">
                                    <Badge
                                        v-for="role in user.roles"
                                        :key="role.name"
                                        variant="secondary"
                                        class="whitespace-nowrap"
                                    >
                                        {{ role.label }}
                                    </Badge>
                                </div>
                            </TableCell>
                            <TableCell class="text-right" @click.stop>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button
                                            variant="ghost"
                                            class="h-8 w-8 p-0"
                                        >
                                            <span class="sr-only"
                                                >Abrir menu</span
                                            >
                                            <MoreHorizontal class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem
                                            @click="copyEmail(user.email)"
                                        >
                                            <Copy class="mr-1 h-4 w-4" /> E-mail
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem as-child>
                                            <Link :href="team.edit(user.id)">
                                                <Pencil
                                                    class="mr-1 h-4 w-4"
                                                />Editar
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            class="text-destructive"
                                            @click="confirmDeletion(user)"
                                        >
                                            <TrashIcon
                                                class="mr-1 h-4 w-4"
                                            />Excluir
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="users.data.length === 0">
                            <TableCell colspan="4" class="h-24 text-center">
                                Nenhum membro encontrado.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            <Pagination :meta="users.meta" />
        </div>

        <Dialog v-model:open="isConfirmingDeletion">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle
                        >Você tem certeza que deseja remover esse
                        membro?</DialogTitle
                    >
                    <DialogDescription>
                        Esta ação não pode ser desfeita. Isso excluirá
                        permanentemente o usuário
                        <strong>{{ userToDelete?.name }}</strong> e removerá
                        seus dados de nossos servidores.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="isConfirmingDeletion = false"
                    >
                        Cancelar
                    </Button>
                    <Button variant="destructive" @click="deleteUser">
                        Excluir
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
