<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import {
    CheckCircle2,
    FileText,
    FileX2,
    FolderOpen,
    HelpCircle,
    XCircle,
} from '@lucide/vue';
import { TabsContent, TabsList, TabsRoot, TabsTrigger } from 'reka-ui';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dashboard } from '@/routes';
import selectionRoute from '@/routes/selection';

type Document = {
    name: string;
    label: string | null;
    filename: string | null;
    path: string | null;
    url: string | null;
};

type Project = {
    id: number;
    candidate_name: string;
    register_id: string;
    title: string;
    documents: Document[];
};

type StorageDocument = {
    name: string;
    path: string;
    url: string;
    is_used: boolean;
    is_import_spreadsheet: boolean;
    project: {
        id: number;
        candidate_name: string;
    } | null;
};

type ViewableDocument = Document | StorageDocument;

const props = defineProps<{
    selection: {
        data: {
            id: number;
            name: string;
            year: number;
        };
    };
    phases: Array<{ name: string; value: string; label: string }>;
    projects: Project[];
    storageDocuments: StorageDocument[];
}>();

const isDocumentDialogOpen = ref(false);
const selectedDocument = ref<ViewableDocument | null>(null);

function openDocument(document: ViewableDocument): void {
    if (!document.url && !('path' in document && document.path)) {
        return;
    }

    selectedDocument.value = document;
    isDocumentDialogOpen.value = true;
}

function documentUrl(document: ViewableDocument): string | null {
    return document.url ?? null;
}

setLayoutProps({
    breadcrumbs: [
        { title: 'Painel', href: dashboard().url },
        {
            title: props.selection.data.name,
            href: selectionRoute.show(props.selection.data.id).url,
        },
        { title: 'Arquivos', href: '#' },
    ],
});
</script>

<template>
    <Head :title="`${selection.data.name}: Arquivos`" />

    <Heading
        title="Arquivos do processo seletivo"
        :description="`${selection.data.name} (${selection.data.year})`"
        :icon="FolderOpen"
    />

    <TabsRoot default-value="storage" class="space-y-4">
        <TabsList
            class="inline-flex h-10 items-center justify-center rounded-md bg-muted p-1 text-muted-foreground"
        >
            <TabsTrigger
                value="storage"
                class="inline-flex items-center justify-center rounded-sm px-3 py-1.5 text-sm font-medium whitespace-nowrap transition-all data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm"
            >
                Todos os arquivos
            </TabsTrigger>
            <TabsTrigger
                value="projects"
                class="inline-flex items-center justify-center rounded-sm px-3 py-1.5 text-sm font-medium whitespace-nowrap transition-all data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm"
            >
                Usados em projetos
            </TabsTrigger>
        </TabsList>

        <TabsContent value="projects" class="mt-0">
            <div class="overflow-hidden rounded-xl border bg-card">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Projeto</TableHead>
                            <TableHead>Documento</TableHead>
                            <TableHead class="w-32 text-right"
                                >Acesso</TableHead
                            >
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-for="project in projects" :key="project.id">
                            <TableRow
                                v-for="(document, index) in project.documents"
                                :key="`${project.id}-${index}`"
                            >
                                <TableCell>
                                    <Badge variant="secondary">{{
                                        project.candidate_name
                                    }}</Badge>
                                    <p
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        {{ project.register_id }} ·
                                        {{ project.title }}
                                    </p>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <FileText
                                            class="h-4 w-4 text-muted-foreground"
                                        />
                                        <div>
                                            <p class="font-medium">
                                                {{ document.name }}
                                            </p>
                                            <p
                                                v-if="document.label"
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{ document.label }}
                                            </p>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">
                                    <button
                                        v-if="document.url || document.path"
                                        type="button"
                                        class="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                                        @click="openDocument(document)"
                                    >
                                        Abrir
                                    </button>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 text-sm text-muted-foreground"
                                    >
                                        <FileX2 class="h-4 w-4" />
                                        Indisponível
                                    </span>
                                </TableCell>
                            </TableRow>
                            <TableRow
                                v-if="project.documents.length === 0"
                                :key="`${project.id}-empty`"
                            >
                                <TableCell>
                                    <Badge variant="secondary">{{
                                        project.candidate_name
                                    }}</Badge>
                                    <p
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        {{ project.register_id }} ·
                                        {{ project.title }}
                                    </p>
                                </TableCell>
                                <TableCell
                                    colspan="2"
                                    class="text-muted-foreground"
                                >
                                    Nenhum arquivo associado a este projeto.
                                </TableCell>
                            </TableRow>
                        </template>
                        <TableRow v-if="projects.length === 0">
                            <TableCell
                                colspan="3"
                                class="py-10 text-center text-muted-foreground"
                            >
                                Nenhum projeto encontrado neste processo
                                seletivo.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </TabsContent>

        <TabsContent value="storage" class="mt-0">
            <div class="overflow-hidden rounded-xl border bg-card">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Arquivo</TableHead>
                            <TableHead>Projeto associado</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="w-32 text-right"
                                >Acesso</TableHead
                            >
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="document in storageDocuments"
                            :key="document.path"
                        >
                            <TableCell>
                                <div class="flex items-center gap-2">
                                    <FileText
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    <span class="font-medium">{{
                                        document.name
                                    }}</span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Badge
                                    v-if="document.project"
                                    variant="secondary"
                                >
                                    {{ document.project.candidate_name }}
                                </Badge>
                                <span
                                    v-else
                                    class="text-sm text-muted-foreground"
                                >
                                    Sem projeto associado
                                </span>
                            </TableCell>
                            <TableCell>
                                <Badge
                                    v-if="document.is_import_spreadsheet"
                                    variant="outline"
                                >
                                    <HelpCircle class="mr-1 h-3 w-3" />
                                    Planilha de importação
                                </Badge>
                                <Badge
                                    v-else-if="document.is_used"
                                    variant="secondary"
                                >
                                    <CheckCircle2 class="mr-1 h-3 w-3" />
                                    Utilizado
                                </Badge>
                                <Badge v-else variant="destructive">
                                    <XCircle class="mr-1 h-3 w-3" />
                                    Não utilizado
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                                    @click="openDocument(document)"
                                >
                                    Abrir
                                </button>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="storageDocuments.length === 0">
                            <TableCell
                                colspan="4"
                                class="py-10 text-center text-muted-foreground"
                            >
                                Nenhum arquivo encontrado na pasta do processo.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </TabsContent>
    </TabsRoot>

    <Dialog v-model:open="isDocumentDialogOpen">
        <DialogContent class="h-[90vh] w-[95vw] max-w-[95vw]! lg:max-w-7xl!">
            <DialogHeader>
                <DialogTitle>{{ selectedDocument?.name }}</DialogTitle>
                <DialogDescription>
                    Visualização do arquivo selecionado.
                </DialogDescription>
            </DialogHeader>
            <iframe
                v-if="selectedDocument && documentUrl(selectedDocument)"
                :src="documentUrl(selectedDocument) ?? undefined"
                :title="selectedDocument.name"
                class="h-[70vh] w-full rounded-md border"
            />
        </DialogContent>
    </Dialog>
</template>
