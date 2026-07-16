<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithColumnLimit;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProjectsExcelImport implements ToCollection, WithHeadingRow, WithColumnLimit, SkipsEmptyRows
{
    public function collection(Collection $collection) {}

    public function endColumn(): string
    {
        return 'BT';
    }
}
