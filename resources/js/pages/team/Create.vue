<script setup lang="ts">
import { createForm, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import TeamController from '@/actions/App/Http/Controllers/Team/TeamController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

defineProps<{
    roles: { value: string; label: string }[];
}>();

interface TeamMemberForm {
    name: string;
    email: string;
    password: string;
    roles: string[];
}

const TypedForm = createForm<TeamMemberForm>();

const selectedRole = ref<string[]>([]);
</script>

<template>
    <Head title="Criar membro da equipe" />
    <Heading
        variant="small"
        title="Novo membro"
        description="Crie um novo membro da equipe"
    />

    <div class="flex max-w-xl flex-col space-y-6">
        <TypedForm
            v-bind="TeamController.store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label htmlFor="name">Nome</Label>
                <Input
                    id="name"
                    class="mt-1 block w-full"
                    name="name"
                    required
                    autocomplete="name"
                    placeholder="Nome completo"
                />
                <InputError class="mt-2" :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label htmlFor="email">E-mail</Label>
                <Input
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    name="email"
                    required
                    autocomplete="username"
                    placeholder="Endereço de e-mail"
                />
                <InputError class="mt-2" :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label htmlFor="password">Senha</Label>
                <Input
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="Senha"
                />
                <InputError class="mt-2" :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label htmlFor="roles">Papel</Label>
                <Select v-model="selectedRole" :multiple="true">
                    <SelectTrigger>
                        <SelectValue placeholder="Selecione os papéis" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="role in roles"
                            :key="role.value"
                            :value="role.value"
                        >
                            {{ role.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <template v-for="role in selectedRole" :key="role">
                    <input type="hidden" name="roles[]" :value="role" />
                </template>
                <InputError class="mt-2" :message="errors.roles" />
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing" data-test="store-team-button">
                    Salvar
                </Button>
            </div>
        </TypedForm>
    </div>
</template>
