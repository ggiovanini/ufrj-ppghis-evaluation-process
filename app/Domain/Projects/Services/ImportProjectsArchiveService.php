<?php

namespace App\Domain\Projects\Services;

use App\Domain\Projects\Exceptions\ValidateImportException;
use App\Models\SelectionProcess;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ImportProjectsArchiveService
{
    /** @var Collection<int, array{project_id: string, document_name: string, path: string, url: string}> */
    private Collection $filesIndex;

    public function __construct()
    {
        $this->filesIndex = collect();
    }

    public function extract(string $filename, SelectionProcess $selection): string
    {
        $disk = Storage::disk('local');

        if (! $disk->exists('inbox/'.$filename)) {
            throw new ValidateImportException('O arquivo ZIP selecionado não está disponível.');
        }

        $archive = new ZipArchive;
        if ($archive->open($disk->path('inbox/'.$filename)) !== true) {
            throw new ValidateImportException('Não foi possível abrir o arquivo ZIP.');
        }

        $destination = Str::slug($selection->name);
        $spreadsheetPath = null;

        try {
            for ($index = 0; $index < $archive->numFiles; $index++) {
                $entryName = $archive->getNameIndex($index);

                if ($entryName === false || str_ends_with($entryName, '/')) {
                    continue;
                }

                $safeEntryName = $this->safeEntryName($entryName);
                $content = $archive->getFromIndex($index);

                if ($content === false) {
                    throw new ValidateImportException('Não foi possível extrair um arquivo do ZIP.');
                }

                $targetPath = $destination.'/'.$safeEntryName;
                Storage::disk('public')->put($targetPath, $content);

                if (strtolower(pathinfo($safeEntryName, PATHINFO_EXTENSION)) !== 'xlsx') {
                    $this->addToFilesIndex($safeEntryName, $targetPath);
                }

                if (strtolower(pathinfo($safeEntryName, PATHINFO_EXTENSION)) === 'xlsx') {
                    $spreadsheetPath ??= Storage::disk('public')->path($targetPath);
                }
            }
        } finally {
            $archive->close();
        }

        if ($spreadsheetPath === null) {
            throw new ValidateImportException('O arquivo ZIP não contém uma planilha .xlsx.');
        }

        return $spreadsheetPath;
    }

    /**
     * @return Collection<int, array{project_id: string, document_name: string, path: string, url: string}>
     */
    public function filesIndex(): Collection
    {
        return $this->filesIndex;
    }

    public function moveToOutbox(string $filename): void
    {
        $disk = Storage::disk('local');
        $source = 'inbox/'.$filename;

        if (! $disk->exists($source)) {
            throw new ValidateImportException('O arquivo ZIP original não está disponível para arquivamento.');
        }

        $disk->move($source, 'outbox/'.$filename);
    }

    private function safeEntryName(string $entryName): string
    {
        $normalized = str_replace('\\', '/', $entryName);
        $parts = explode('/', $normalized);

        if ($normalized === '' || str_starts_with($normalized, '/') || in_array('..', $parts, true)) {
            throw new ValidateImportException('O ZIP contém um caminho de arquivo inválido.');
        }

        return implode('/', array_filter($parts, fn (string $part): bool => $part !== '.'));
    }

    private function addToFilesIndex(string $filename, string $path): void
    {
        $basename = basename($filename);
        if (! preg_match('/^([^_]+)_[^_]+_[^-]+-(.+)$/', $basename, $matches)) {
            return;
        }

        $this->filesIndex->push([
            'project_id' => $matches[1],
            'document_name' => $matches[2],
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ]);
    }
}
