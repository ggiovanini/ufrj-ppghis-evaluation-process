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
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import selectionRoute from '@/routes/selection';
import type { SelectionProcess } from '@/types/selection-process';

const props = defineProps<{
    selection: {
        data: SelectionProcess;
    };
    inboxFiles: string[];
}>();

const form = useForm({
    file: null as File | null,
    inbox_file: '',
    modality: 'both',
});

const submit = () => {
    form.post(
        selectionRoute.import({ selection: props.selection.data.id }).url,
        {
            onSuccess: () => {
                form.reset();
            },
        },
    );
};

const onFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;

    if (target.files && target.files.length > 0) {
        form.file = target.files[0];
        form.inbox_file = '';
    }
};

const onInboxFileChange = (e: Event) => {
    const target = e.target as HTMLSelectElement;

    form.inbox_file = target.value;

    if (target.value) {
        form.file = null;
    }
};

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Painel',
            href: dashboard().url,
        },
        {
            title: props.selection.data.name,
            href: selectionRoute.show(props.selection.data.id).url,
        },
        {
            title: 'Importação',
            href: '#',
        },
    ],
});
</script>

<template>
    <Head :title="selection.data.name" />

    <div class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div
            class="relative flex flex-1 flex-col items-center justify-center overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <PlaceholderPattern class="z-0" />
            <Card class="z-10 w-lg outline-2 outline-foreground/10">
                <CardHeader>
                    <CardTitle>Importar projetos</CardTitle>
                    <CardDescription>
                        Selecione um ZIP disponível no servidor ou envie um
                        arquivo .xlsx com a listagem dos projetos.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid w-full items-center gap-1.5">
                            <Label for="modality"
                                >Modalidade dos projetos</Label
                            >
                            <select
                                id="modality"
                                v-model="form.modality"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <option value="both">
                                    Mestrado e doutorado
                                </option>
                                <option value="master">Apenas mestrado</option>
                                <option value="doctorate">
                                    Apenas doutorado
                                </option>
                            </select>
                            <div
                                v-if="form.errors.modality"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.modality }}
                            </div>
                        </div>

                        <div class="grid w-full items-center gap-1.5">
                            <Label for="inbox_file"
                                >ZIP disponível no servidor</Label
                            >
                            <select
                                id="inbox_file"
                                v-model="form.inbox_file"
                                @change="onInboxFileChange"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <option value="">
                                    Selecione um arquivo ZIP
                                </option>
                                <option
                                    v-for="inboxFile in inboxFiles"
                                    :key="inboxFile"
                                    :value="inboxFile"
                                >
                                    {{ inboxFile }}
                                </option>
                            </select>
                            <div
                                v-if="form.errors.inbox_file"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.inbox_file }}
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-2 text-sm text-muted-foreground"
                        >
                            <span class="h-px flex-1 bg-border"></span>
                            ou envie uma planilha
                            <span class="h-px flex-1 bg-border"></span>
                        </div>

                        <div class="grid w-full items-center gap-1.5">
                            <Label for="file">Arquivo excel (.xlsx)</Label>
                            <input
                                id="file"
                                type="file"
                                accept=".xlsx, .xls"
                                @change="onFileChange"
                                placeholder="Selecione um arquivo"
                                class="flex h-9 w-full cursor-pointer rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            />
                            <div
                                v-if="form.errors.file"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.file }}
                            </div>
                        </div>

                        <div
                            v-if="form.progress"
                            class="h-2.5 w-full rounded-full bg-secondary"
                        >
                            <div
                                class="h-2.5 rounded-full bg-primary"
                                :style="{
                                    width: `${form.progress.percentage}%`,
                                }"
                            ></div>
                        </div>

                        <div class="flex justify-end">
                            <Button
                                type="submit"
                                :disabled="
                                    form.processing ||
                                    (!form.file && !form.inbox_file)
                                "
                            >
                                {{
                                    form.processing
                                        ? 'Importando...'
                                        : 'Iniciar Importação'
                                }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
