<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithColumnLimit;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProjectsExcelImport implements SkipsEmptyRows, ToCollection, WithColumnLimit, WithHeadingRow
{
    public function collection(Collection $collection) {}

    public function endColumn(): string
    {
        return 'BB';
    }
}
