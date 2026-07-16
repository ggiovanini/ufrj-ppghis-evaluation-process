<?php

namespace Tests\Feature\SelectionProcess;

use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

test('it can import projects through the controller', function () {
    // Garantir que a permissão exista
    Permission::create(['name' => 'projects.import', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->givePermissionTo('projects.import');

    $selectionProcess = SelectionProcess::factory()->create();

    $filePath = base_path('tests/Fixtures/projects_import_template.xlsx');
    $file = new UploadedFile($filePath, 'projects_import_template.xlsx', null, null, true);

    $response = $this->actingAs($user)
        ->post(route('selection.import', ['selection' => $selectionProcess->id]), [
            'file' => $file,
        ]);
    $response->assertRedirect(route('selection.projects.index', ['selection' => $selectionProcess->id]));

    // Verificar se o projeto foi criado no banco
    $this->assertDatabaseHas('projects', [
        'selection_process_id' => $selectionProcess->id,
        'candidate_name' => 'VANIA BARYNER DE BARROS',
        'title' => 'Políticas educacionais do Movimento Zapatista e do Movimento dos Trabalhadores Rurais Sem Terra (MST) Onde se aproximam e se afastam',
    ]);
});

test('it prevents import without permission', function () {
    $user = User::factory()->create();
    $selectionProcess = SelectionProcess::factory()->create();
    $filePath = base_path('tests/Fixtures/projects_import_template.xlsx');
    $file = new UploadedFile($filePath, 'projects_import_template.xlsx', null, null, true);

    $response = $this->actingAs($user)
        ->post(route('selection.import', ['selection' => $selectionProcess->id]), [
            'file' => $file,
        ]);

    $response->assertForbidden();
});
