<script setup lang="ts">
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
import type { ProjectWithDetail as Project } from '@/types/projects';

defineProps<{
    project: Project;
}>();

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

const getFileUrl = (document: { url?: string; filename: string }) =>
    document.url ?? `/storage/uploads/${document.filename}`;

const isImageDocument = (document: ProjectDocument) =>
    ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'].includes(
        (document.ext ?? document.name.split('.').pop() ?? '').toLowerCase(),
    );
</script>

<template>
    <div class="z-10 space-y-6">
        <div v-if="project.content?.documents?.length" class="space-y-4">
            <h4 class="text-lg font-semibold">Projeto</h4>
            <div class="grid gap-4 sm:grid-cols-2">
                <div
                    v-for="(doc, index) in project.content.documents.filter(
                        (item) => item.label.toLocaleLowerCase() === 'projeto',
                    )"
                    :key="index"
                    class="flex items-center justify-between rounded-lg border bg-card p-4"
                >
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="shrink-0 rounded-md bg-muted p-2">
                            <FileText class="h-5 w-5 text-muted-foreground" />
                        </div>
                        <div class="space-y-1 overflow-hidden">
                            <p
                                class="truncate text-sm leading-none font-medium uppercase"
                            >
                                {{ doc.label }}
                            </p>
                            <p class="truncate text-xs text-muted-foreground">
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
