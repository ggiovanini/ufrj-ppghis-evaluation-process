<script setup lang="ts">
import { Head, useForm, setLayoutProps } from '@inertiajs/vue3';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard } from '@/routes';
import selection from '@/routes/selection';

defineProps<{
    reviewForms: Array<{ id: number; name: string; version: string }>;
}>();

const form = useForm({
    name: '',
    description: '',
    year: new Date().getFullYear(),
    review_form_id: '',
});

const submit = () => {
    form.post(selection.store().url);
};

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Painel',
            href: dashboard().url,
        },
        {
            title: 'Criação',
            href: '#',
        },
    ],
});
</script>

<template>
    <Head title="Criar Processo de Seleção" />

    <div class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div
            class="relative flex flex-1 items-center justify-center overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <PlaceholderPattern class="z-0" />
            <Card class="z-10 w-lg outline-2 outline-foreground/10">
                <CardHeader>
                    <CardTitle>Novo Processo de Seleção</CardTitle>
                    <CardDescription>
                        Crie um novo processo de seleção para importar projetos
                        e iniciar avaliações.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="name">Nome do Processo</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="Ex: Seleção de Mestrado 2026"
                            />
                            <div
                                v-if="form.errors.name"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="description">Descrição</Label>
                            <Input
                                id="description"
                                v-model="form.description"
                                placeholder="Uma breve descrição..."
                            />
                            <div
                                v-if="form.errors.description"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.description }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="year">Ano</Label>
                            <Input
                                id="year"
                                type="number"
                                v-model="form.year"
                            />
                            <div
                                v-if="form.errors.year"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.year }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="review_form"
                                >Formulário de Avaliação</Label
                            >
                            <Select v-model="form.review_form_id">
                                <SelectTrigger id="review_form">
                                    <SelectValue
                                        placeholder="Selecione um formulário"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="rf in reviewForms"
                                        :key="rf.id"
                                        :value="rf.id.toString()"
                                    >
                                        {{ rf.name }} ({{ rf.version }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div
                                v-if="form.errors.review_form_id"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.review_form_id }}
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <Button type="submit" :disabled="form.processing">
                                Criar Processo
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
