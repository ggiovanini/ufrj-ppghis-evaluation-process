<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProjectsImportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => [
                'nullable',
                'required_without:inbox_file',
                Rule::prohibitedIf(fn (): bool => $this->filled('inbox_file')),
                'file',
                'mimes:xls,xlsx',
                'max:2048',
            ],
            'inbox_file' => [
                'nullable',
                'required_without:file',
                Rule::prohibitedIf(fn (): bool => $this->hasFile('file')),
                'string',
                Rule::in($this->availableInboxFiles()),
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function availableInboxFiles(): array
    {
        return collect(Storage::disk('local')->files('inbox'))
            ->filter(fn (string $path): bool => strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'zip')
            ->map(fn (string $path): string => basename($path))
            ->values()
            ->all();
    }

    public function authorize(): bool
    {
        return $this->user()->can('projects.import');
    }
}
