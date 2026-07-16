<script setup lang="ts">
import { createForm, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import TeamController from '@/actions/App/Http/Controllers/Team/TeamController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const props = defineProps<{
    user: {
        data: {
            id: number;
            name: string;
            email: string;
            roles: { value: string; label: string }[];
        };
    };
    roles: { value: string; label: string }[];
}>();

interface TeamMemberForm {
    name: string;
    email: string;
    password?: string;
    roles?: string[];
}

const TypedForm = createForm<TeamMemberForm>();

const keepPassword = ref(true);
const keepRole = ref(true);
const selectedRole = ref<string[]>(props.user.data.roles.map((r) => r.value));
</script>

<template>
    <Head title="Editar membro da equipe" />
    <Heading
        variant="small"
        :title="props.user.data.name"
        description="Edite o membro da equipe"
    />

    <div class="flex max-w-xl flex-col space-y-6">
        <TypedForm
            v-bind="TeamController.update.form(props.user.data.id)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label htmlFor="name">Nome</Label>
                <Input
                    id="name"
                    class="mt-1 block w-full"
                    name="name"
                    :default-value="props.user.data.name"
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
                    :default-value="props.user.data.email"
                    required
                    autocomplete="username"
                    placeholder="Endereço de e-mail"
                />
                <InputError class="mt-2" :message="errors.email" />
            </div>

            <div class="space-y-4">
                <div class="flex items-center space-x-2">
                    <Checkbox id="keepPassword" v-model="keepPassword" />
                    <Label htmlFor="keepPassword" class="cursor-pointer">
                        Manter a senha atual
                    </Label>
                </div>

                <div v-if="!keepPassword" class="grid gap-2">
                    <Label htmlFor="password">Nova Senha</Label>
                    <Input
                        id="password"
                        type="password"
                        class="mt-1 block w-full"
                        name="password"
                        autocomplete="new-password"
                        placeholder="Digite a nova senha"
                    />
                    <InputError class="mt-2" :message="errors.password" />
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex items-center space-x-2">
                    <Checkbox id="keepRole" v-model="keepRole" />
                    <Label htmlFor="keepRole" class="cursor-pointer">
                        Manter o papel atual
                    </Label>
                </div>

                <div v-if="!keepRole" class="grid gap-2">
                    <Label htmlFor="roles">Papel</Label>
                    <Select v-model="selectedRole" :multiple="true">
                        <SelectTrigger>
                            <SelectValue placeholder="Selecione os papéis" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="role in props.roles"
                                :key="role.value"
                                :value="role.value"
                            >
                                {{ role.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError class="mt-2" :message="errors.roles" />
                </div>
                <template v-if="!keepRole">
                    <input
                        v-for="role in selectedRole"
                        :key="role"
                        type="hidden"
                        name="roles[]"
                        :value="role"
                    />
                </template>
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing" data-test="update-team-button">
                    Salvar
                </Button>
            </div>
        </TypedForm>
    </div>
</template>
