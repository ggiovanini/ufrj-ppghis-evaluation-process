<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;

class ProjectsImportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'mimes:xls,xlsx', 'max:2048'],
        ];
    }

    public function authorize(): bool
    {
        return $this->user()->can('projects.import');
    }
}
