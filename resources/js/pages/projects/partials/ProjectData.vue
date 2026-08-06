<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Download, ExternalLink, FileText } from '@lucide/vue';
import { ref } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { authCan } from '@/types';
import type { ProjectWithDetail as Project } from '@/types/projects';

defineProps<{
    project: Project;
}>();

const page = usePage();
const auth = page.props.auth;

const activeTab = ref('info');

type ProjectDocument = {
    label: string;
    name: string;
    filename: string;
    ext?: string;
    url?: string;
};

const selectedDocument = ref<ProjectDocument | null>(null);

const openDocument = (document: ProjectDocument) => {
    selectedDocument.value = document;
};

const closeDocument = () => {
    selectedDocument.value = null;
};

const formatKey = (key: string | number) => {
    if (typeof key === 'number') {
        return key.toString();
    }

    return key.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());
};

const isObject = (val: any) =>
    val !== null && typeof val === 'object' && !Array.isArray(val);

const getFileUrl = (document: { url?: string; filename: string }) =>
    document.url ?? `/storage/uploads/${document.filename}`;

const isImageDocument = (document: ProjectDocument) =>
    ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'].includes(
        (document.ext ?? document.name.split('.').pop() ?? '').toLowerCase(),
    );
</script>

<template>
    <div class="z-10 space-y-6">
        <div class="flex items-center gap-4 border-b">
            <button
                @click="activeTab = 'info'"
                :class="[
                    'border-b-2 pb-4 text-sm font-medium transition-colors outline-none',
                    activeTab === 'info'
                        ? 'border-primary text-primary'
                        : 'border-transparent text-muted-foreground hover:text-foreground',
                ]"
            >
                Informações normalizadas
            </button>
            <button
                v-if="authCan(auth, 'projects.manage')"
                @click="activeTab = 'original'"
                :class="[
                    'border-b-2 pb-4 text-sm font-medium transition-colors outline-none',
                    activeTab === 'original'
                        ? 'border-primary text-primary'
                        : 'border-transparent text-muted-foreground hover:text-foreground',
                ]"
            >
                Importação original
            </button>
        </div>

        <!-- Tab: Informações -->
        <div v-if="activeTab === 'info'" class="space-y-8">
            <div v-if="project.content?.documents?.length" class="space-y-4">
                <h4 class="text-lg font-semibold">Documentos</h4>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div
                        v-for="(doc, index) in project.content.documents"
                        :key="index"
                        class="flex items-center justify-between rounded-lg border bg-card p-4"
                    >
                        <div class="flex items-center gap-3 overflow-hidden">
                            <div class="shrink-0 rounded-md bg-muted p-2">
                                <FileText
                                    class="h-5 w-5 text-muted-foreground"
                                />
                            </div>
                            <div class="space-y-1 overflow-hidden">
                                <p
                                    class="truncate text-sm leading-none font-medium uppercase"
                                >
                                    {{ doc.label }}
                                </p>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ doc.name }}
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="openDocument(doc)"
                            class="shrink-0 rounded-md p-2 transition-colors hover:bg-muted"
                            title="Visualizar arquivo"
                        >
                            <ExternalLink class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="project.content?.content?.length" class="space-y-6">
                <h4 class="text-lg font-semibold">Detalhes</h4>
                <div
                    class="grid border-separate divide-x divide-y divide-muted rounded-lg border bg-card sm:grid-cols-2"
                >
                    <div
                        v-for="(item, index) in project.content.content"
                        :key="index"
                        class="space-y-1 p-4"
                    >
                        <h5 class="text-sm text-muted-foreground uppercase">
                            {{ item.label }}
                        </h5>
                        <div
                            v-if="
                                typeof item.value === 'string' &&
                                item.value.indexOf('http') !== -1
                            "
                            class="flex items-center gap-3 overflow-hidden"
                        >
                            <p
                                class="text-sm font-bold whitespace-pre-wrap text-foreground"
                            >
                                {{ item.value }}
                            </p>
                            <a
                                :href="item.value"
                                target="_blank"
                                class="shrink-0 rounded-md p-2 transition-colors hover:bg-muted"
                                title="Abrir arquivo"
                            >
                                <ExternalLink class="h-4 w-4" />
                            </a>
                        </div>
                        <p
                            v-else
                            class="text-sm font-bold whitespace-pre-wrap text-foreground"
                        >
                            {{ item.value }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-if="
                    !project.content?.content?.length &&
                    !project.content?.documents?.length
                "
                class="py-12 text-center text-muted-foreground"
            >
                Nenhuma informação normalizada disponível.
            </div>
        </div>

        <!-- Tab: Importação Original -->
        <div v-if="activeTab === 'original'" class="space-y-4">
            <div
                class="grid border-separate divide-y divide-muted rounded-lg border bg-card sm:grid-cols-1"
            >
                <template v-for="(value, key) in project.original_content">
                    <div v-if="value" :key="key" class="space-y-1 p-4">
                        <h5
                            class="text-sm font-bold text-foreground capitalize"
                        >
                            {{ formatKey(key) }}
                        </h5>
                        <div
                            v-if="isObject(value)"
                            class="mt-2 border-l-2 border-muted pl-4"
                        >
                            <div
                                v-for="(subVal, subKey) in value"
                                :key="subKey"
                                class="mb-2"
                            >
                                <span
                                    class="text-xs font-semibold text-muted-foreground uppercase"
                                >
                                    {{ formatKey(subKey) }}:
                                </span>
                                <p class="text-sm">
                                    {{ subVal }}
                                </p>
                            </div>
                        </div>
                        <p
                            v-else
                            class="text-sm whitespace-pre-wrap text-muted-foreground"
                        >
                            {{ value }}
                        </p>
                    </div>
                </template>
            </div>
        </div>

        <Dialog
            :open="selectedDocument !== null"
            @update:open="(open) => !open && closeDocument()"
        >
            <DialogContent
                class="flex h-[90vh] w-[95vw] max-w-[95vw]! flex-col lg:max-w-7xl!"
            >
                <DialogHeader>
                    <DialogTitle>{{ selectedDocument?.name }}</DialogTitle>
                    <DialogDescription>
                        {{ selectedDocument?.label }}
                    </DialogDescription>
                </DialogHeader>

                <div
                    class="flex min-h-0 flex-1 items-center justify-center overflow-hidden rounded-lg border bg-muted"
                >
                    <img
                        v-if="
                            selectedDocument &&
                            isImageDocument(selectedDocument)
                        "
                        :src="getFileUrl(selectedDocument)"
                        :alt="selectedDocument.name"
                        class="max-h-full max-w-full object-contain"
                    />
                    <iframe
                        v-else-if="selectedDocument"
                        :src="getFileUrl(selectedDocument)"
                        :title="`Visualização de ${selectedDocument.name}`"
                        class="h-full min-h-[50vh] w-full"
                    />
                </div>

                <DialogFooter class="gap-2 sm:justify-end">
                    <a
                        v-if="selectedDocument"
                        :href="getFileUrl(selectedDocument)"
                        download
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                    >
                        <Download class="h-4 w-4" />
                        Baixar arquivo
                    </a>
                    <a
                        v-if="selectedDocument"
                        :href="getFileUrl(selectedDocument)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-muted"
                    >
                        <ExternalLink class="h-4 w-4" />
                        Abrir em nova janela
                    </a>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
