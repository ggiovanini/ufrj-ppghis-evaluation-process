<?php

namespace Tests\Feature\SelectionProcess;

use App\Models\Project;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use ZipArchive;

uses(RefreshDatabase::class);

test('it can import projects through the controller', function () {
    // Garantir que a permissão exista
    Permission::create(['name' => 'projects.import', 'guard_name' => 'web']);
    Role::create(['name' => 'reviewer', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->givePermissionTo('projects.import');

    $selectionProcess = SelectionProcess::factory()->create();

    $filePath = base_path('tests/Fixtures/projects_import_template.xlsx');
    $file = new UploadedFile($filePath, 'projects_import_template.xlsx', null, null, true);

    $response = $this->actingAs($user)
        ->post(route('selection.import', ['selection' => $selectionProcess->id]), [
            'file' => $file,
            'modality' => 'both',
        ]);
    $response->assertRedirect(route('selection.show', ['selection' => $selectionProcess->id]));

    // Verificar se o projeto foi criado no banco
    $this->assertDatabaseHas('projects', [
        'selection_process_id' => $selectionProcess->id,
        'candidate_name' => 'VANIA BARYNER DE BARROS',
        'title' => 'Políticas educacionais do Movimento Zapatista e do Movimento dos Trabalhadores Rurais Sem Terra (MST) Onde se aproximam e se afastam',
        'submitted_at' => '2025-06-17 13:22:37',
    ]);

    $project = Project::where('candidate_name', 'VANIA BARYNER DE BARROS')->first();
    expect($project->content)->not->toBeNull();
    expect($project->content['documents'])->not->toBeEmpty();
});

test('it filters projects by modality and does not duplicate external registrations', function () {
    Permission::create(['name' => 'projects.import', 'guard_name' => 'web']);
    Role::create(['name' => 'reviewer', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->givePermissionTo('projects.import');
    $selectionProcess = SelectionProcess::factory()->create();
    $filePath = base_path('tests/Fixtures/projects_import_template.xlsx');

    $response = $this->actingAs($user)->post(
        route('selection.import', ['selection' => $selectionProcess->id]),
        [
            'file' => new UploadedFile($filePath, 'projects_import_template.xlsx', null, null, true),
            'modality' => 'doctorate',
        ],
    );

    $response->assertRedirect(route('selection.show', ['selection' => $selectionProcess->id]));
    expect($selectionProcess->projects()->where('modality', 'doctorate')->count())->toBe(83)
        ->and($selectionProcess->projects()->where('modality', 'master')->count())->toBe(0);

    $this->actingAs($user)->post(
        route('selection.import', ['selection' => $selectionProcess->id]),
        [
            'file' => new UploadedFile($filePath, 'projects_import_template.xlsx', null, null, true),
            'modality' => 'both',
        ],
    );

    $this->actingAs($user)->post(
        route('selection.import', ['selection' => $selectionProcess->id]),
        [
            'file' => new UploadedFile($filePath, 'projects_import_template.xlsx', null, null, true),
            'modality' => 'both',
        ],
    );

    expect($selectionProcess->projects()->count())->toBe(266);
});

test('it prevents import without permission', function () {
    $user = User::factory()->create();
    $selectionProcess = SelectionProcess::factory()->create();
    $filePath = base_path('tests/Fixtures/projects_import_template.xlsx');
    $file = new UploadedFile($filePath, 'projects_import_template.xlsx', null, null, true);

    $response = $this->actingAs($user)
        ->post(route('selection.import', ['selection' => $selectionProcess->id]), [
            'file' => $file,
            'modality' => 'both',
        ]);

    $response->assertForbidden();
});

test('it lists and imports a ZIP from the inbox', function () {
    Permission::create(['name' => 'projects.import', 'guard_name' => 'web']);
    Role::create(['name' => 'reviewer', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->givePermissionTo('projects.import');
    $selectionProcess = SelectionProcess::factory()->create(['name' => 'Seleção PPGHIS 2027']);

    Storage::fake('local');
    Storage::fake('public');
    Storage::disk('local')->put('inbox/projects.zip', '');

    $archive = new ZipArchive;
    $archive->open(Storage::disk('local')->path('inbox/projects.zip'));
    $archive->addFile(base_path('tests/Fixtures/projects_import_template.xlsx'), 'imports/projects.xlsx');
    $archive->addFromString('documents/readme.txt', 'Conteúdo extraído');
    $archive->addFromString(
        '00023_06-Q128_00-HISTORICO-VANIA-BRAYNER.pdf',
        'Histórico da candidata',
    );
    $archive->close();

    $this->actingAs($user)
        ->get(route('selection.prepare', ['selection' => $selectionProcess->id]))
        ->assertInertia(fn ($page) => $page->where('inboxFiles', ['projects.zip']));

    $response = $this->actingAs($user)
        ->post(route('selection.import', ['selection' => $selectionProcess->id]), [
            'inbox_file' => 'projects.zip',
            'modality' => 'both',
        ]);

    $response->assertRedirect(route('selection.show', ['selection' => $selectionProcess->id]));
    Storage::disk('local')->assertMissing('inbox/projects.zip');
    Storage::disk('local')->assertExists('outbox/projects.zip');
    Storage::disk('public')->assertExists('selecao-ppghis-2027/imports/projects.xlsx');
    Storage::disk('public')->assertExists('selecao-ppghis-2027/documents/readme.txt');
    Storage::disk('public')->assertExists('selecao-ppghis-2027/00023_06-Q128_00-HISTORICO-VANIA-BRAYNER.pdf');
    $this->assertDatabaseHas('projects', [
        'selection_process_id' => $selectionProcess->id,
        'candidate_name' => 'VANIA BARYNER DE BARROS',
    ]);

    $project = Project::where('candidate_name', 'VANIA BARYNER DE BARROS')->firstOrFail();
    $document = collect($project->content['documents'])
        ->firstWhere('name', 'HISTORICO-VANIA-BRAYNER.pdf');

    expect($document['path'])->toBe('selecao-ppghis-2027/00023_06-Q128_00-HISTORICO-VANIA-BRAYNER.pdf')
        ->and($document['url'])->toEndWith('/storage/selecao-ppghis-2027/00023_06-Q128_00-HISTORICO-VANIA-BRAYNER.pdf');
});
