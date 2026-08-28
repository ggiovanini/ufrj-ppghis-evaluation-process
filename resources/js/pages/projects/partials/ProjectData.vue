<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import {
    Download,
    ExternalLink,
    FileText,
    FileX2,
    History,
    MoreHorizontal,
    Upload,
} from '@lucide/vue';
import { ref } from 'vue';
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
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import routeProjects from '@/routes/selection/projects';
import { authCan } from '@/types';
import type { ProjectWithDetail as Project } from '@/types/projects';

const props = defineProps<{
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
    path?: string;
    url?: string;
};

type DocumentVersion = {
    id: number;
    label: string;
    name: string;
    url: string;
    version: number;
    action: string;
    size?: number;
    created_at: string;
    uploaded_by?: string;
};

const selectedDocument = ref<ProjectDocument | null>(null);
const uploadDialogOpen = ref(false);
const historyDialogOpen = ref(false);
const historyLabel = ref('');
const selectedDocumentIndex = ref<number | null>(null);
const form = useForm<{
    label: string;
    document_index: number | null;
    file: File | null;
}>({
    label: '',
    document_index: null,
    file: null,
});

const documentVersions = (): DocumentVersion[] =>
    (props.project.document_versions ?? []) as DocumentVersion[];

const openUpload = (
    document: ProjectDocument | null = null,
    index: number | null = null,
) => {
    form.reset();
    form.clearErrors();
    form.label = document?.label ?? '';
    form.document_index = index;
    selectedDocumentIndex.value = index;
    uploadDialogOpen.value = true;
};

const submitUpload = () => {
    form.post(
        routeProjects.documents.upload({
            selection: props.project.selection_process_id ?? 0,
            project: props.project.id,
        }).url,
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                uploadDialogOpen.value = false;
                form.reset();
            },
        },
    );
};

const openHistory = (document: ProjectDocument) => {
    historyLabel.value = document.label;
    historyDialogOpen.value = true;
};

const versionsForLabel = (label: string) =>
    documentVersions().filter((version) => version.label === label);

const openDocument = (document: ProjectDocument) => {
    if (!getFileUrl(document)) {
        return;
    }

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

const getFileUrl = (document: { path?: string; url?: string }): string | null =>
    document.url ?? null;

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
            <div class="space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <h4 class="text-lg font-semibold">Documentos</h4>
                    <button
                        v-if="authCan(auth, 'projects.manage')"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm font-medium transition-colors hover:bg-muted"
                        @click="openUpload()"
                    >
                        <Upload class="h-4 w-4" />
                        Adicionar documento
                    </button>
                </div>
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
                        <div class="flex shrink-0 items-center gap-1">
                            <button
                                v-if="getFileUrl(doc)"
                                type="button"
                                @click="openDocument(doc)"
                                class="rounded-md p-2 transition-colors hover:bg-muted"
                                title="Visualizar arquivo"
                            >
                                <ExternalLink class="h-4 w-4" />
                            </button>
                            <DropdownMenu
                                v-if="authCan(auth, 'projects.manage')"
                            >
                                <DropdownMenuTrigger as-child>
                                    <button
                                        type="button"
                                        class="rounded-md p-2 transition-colors hover:bg-muted"
                                        title="Opções do arquivo"
                                    >
                                        <MoreHorizontal class="h-4 w-4" />
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem
                                        @click="openUpload(doc, index)"
                                    >
                                        <Upload class="mr-2 h-4 w-4" />
                                        Substituir arquivo
                                    </DropdownMenuItem>
                                    <DropdownMenuItem @click="openHistory(doc)">
                                        <History class="mr-2 h-4 w-4" /> Ver
                                        histórico
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                            <span
                                v-if="!getFileUrl(doc)"
                                class="inline-flex items-center gap-1 text-xs text-muted-foreground"
                                title="Arquivo indisponível"
                            >
                                <FileX2 class="h-4 w-4" />
                                Indisponível
                            </span>
                        </div>
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
                        :src="getFileUrl(selectedDocument) ?? undefined"
                        :alt="selectedDocument.name"
                        class="max-h-full max-w-full object-contain"
                    />
                    <iframe
                        v-else-if="selectedDocument"
                        :src="getFileUrl(selectedDocument) ?? undefined"
                        :title="`Visualização de ${selectedDocument.name}`"
                        class="h-full min-h-[50vh] w-full"
                    />
                </div>

                <DialogFooter class="gap-2 sm:justify-end">
                    <a
                        v-if="selectedDocument && getFileUrl(selectedDocument)"
                        :href="getFileUrl(selectedDocument) ?? undefined"
                        download
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                    >
                        <Download class="h-4 w-4" />
                        Baixar arquivo
                    </a>
                    <a
                        v-if="selectedDocument && getFileUrl(selectedDocument)"
                        :href="getFileUrl(selectedDocument) ?? undefined"
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

        <Dialog v-model:open="uploadDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{
                        selectedDocumentIndex === null
                            ? 'Adicionar documento'
                            : 'Substituir documento'
                    }}</DialogTitle>
                    <DialogDescription
                        >O arquivo anterior será mantido no
                        histórico.</DialogDescription
                    >
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitUpload">
                    <div class="space-y-2">
                        <label for="document-label" class="text-sm font-medium"
                            >Descrição</label
                        >
                        <input
                            id="document-label"
                            v-model="form.label"
                            type="text"
                            class="h-10 w-full rounded-md border bg-transparent px-3 text-sm"
                            placeholder="Ex.: Projeto de pesquisa"
                        />
                        <p
                            v-if="form.errors.label"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.label }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <label for="document-file" class="text-sm font-medium"
                            >Arquivo</label
                        >
                        <input
                            id="document-file"
                            type="file"
                            class="w-full rounded-md border p-2 text-sm"
                            @change="
                                form.file =
                                    ($event.target as HTMLInputElement)
                                        .files?.[0] ?? null
                            "
                        />
                        <p class="text-xs text-muted-foreground">
                            PDF, Office, imagem, TXT ou ZIP. Máximo de 50 MB.
                        </p>
                        <p
                            v-if="form.errors.file"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.file }}
                        </p>
                    </div>
                    <DialogFooter>
                        <button
                            type="button"
                            class="rounded-md border px-4 py-2 text-sm"
                            @click="uploadDialogOpen = false"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                        >
                            {{
                                form.processing
                                    ? 'Enviando...'
                                    : 'Salvar arquivo'
                            }}
                        </button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="historyDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Histórico: {{ historyLabel }}</DialogTitle>
                    <DialogDescription
                        >Versões preservadas deste documento.</DialogDescription
                    >
                </DialogHeader>
                <div
                    v-if="versionsForLabel(historyLabel).length"
                    class="max-h-[50vh] space-y-3 overflow-y-auto"
                >
                    <div
                        v-for="version in versionsForLabel(historyLabel)"
                        :key="version.id"
                        class="flex items-center justify-between gap-4 rounded-md border p-3"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">
                                v{{ version.version }} · {{ version.name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    new Date(version.created_at).toLocaleString(
                                        'pt-BR',
                                    )
                                }}<span v-if="version.uploaded_by">
                                    · {{ version.uploaded_by }}</span
                                >
                            </p>
                        </div>
                        <a
                            :href="version.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="shrink-0 rounded-md border px-3 py-1.5 text-xs hover:bg-muted"
                            >Abrir</a
                        >
                    </div>
                </div>
                <p
                    v-else
                    class="py-6 text-center text-sm text-muted-foreground"
                >
                    Nenhuma versão registrada para este documento.
                </p>
            </DialogContent>
        </Dialog>
    </div>
</template>
