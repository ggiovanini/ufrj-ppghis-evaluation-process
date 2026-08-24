<?php

use App\Domain\Shared\Types\UserRoles;
use App\Models\Project;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => UserRoles::ADMIN->value, 'guard_name' => 'web']);
});

test('administrators can view documents from their current selection process', function () {
    $selection = SelectionProcess::factory()->create(['name' => 'Seleção PPGHIS 2027']);
    $admin = User::factory()->create([
        'current_selection_process_id' => $selection->id,
    ]);
    $admin->assignRole(UserRoles::ADMIN->value);
    Storage::fake('public');

    $projectWithDocument = Project::factory()->create(['candidate_name' => 'Ana Candidata']);
    $projectWithoutDocument = Project::factory()->create(['candidate_name' => 'Bruno Candidato']);

    DB::table('projects')->where('id', $projectWithDocument->id)->update([
        'selection_process_id' => $selection->id,
        'content' => json_encode([
            'documents' => [[
                'name' => 'projeto.pdf',
                'label' => 'Projeto',
                'filename' => 'projeto.pdf',
                'path' => 'selecao-ppghis-2027/projeto.pdf',
                'url' => 'http://localhost/storage/selecao-ppghis-2027/projeto.pdf',
            ]],
        ]),
    ]);
    DB::table('projects')->where('id', $projectWithoutDocument->id)->update([
        'selection_process_id' => $selection->id,
        'content' => json_encode(['documents' => [[
            'name' => 'arquivo-ausente.pdf',
            'path' => null,
            'url' => null,
        ]]]),
    ]);
    Storage::disk('public')->put('selecao-ppghis-2027/projeto.pdf', 'used');
    Storage::disk('public')->put('selecao-ppghis-2027/orphan.pdf', 'unused');
    Storage::disk('public')->put('selecao-ppghis-2027/import.xlsx', 'spreadsheet');

    $this->actingAs($admin)
        ->get(route('selection.documents.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('selection/Documents')
            ->where('selection.data.name', 'Seleção PPGHIS 2027')
            ->has('phases')
            ->has('projects', 2)
            ->where('projects.0.documents.0.name', 'projeto.pdf')
            ->where('projects.0.documents.0.url', 'http://localhost/storage/selecao-ppghis-2027/projeto.pdf')
            ->where('projects.1.documents.0.name', 'arquivo-ausente.pdf')
            ->where('projects.1.documents.0.url', null)
            ->has('storageDocuments', 3)
            ->where('storageDocuments', function ($documents): bool {
                $documentsByName = collect($documents)->keyBy('name');

                return $documentsByName['projeto.pdf']['is_used']
                    && ! $documentsByName['orphan.pdf']['is_used']
                    && $documentsByName['import.xlsx']['is_import_spreadsheet'];
            })
        );
});

test('non administrators cannot view selection process documents', function () {
    $selection = SelectionProcess::factory()->create();
    $user = User::factory()->create([
        'current_selection_process_id' => $selection->id,
    ]);

    $this->actingAs($user)
        ->get(route('selection.documents.index'))
        ->assertForbidden();
});

test('administrators cannot view documents without a selection process', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRoles::ADMIN->value);

    $this->actingAs($admin)
        ->get(route('selection.documents.index'))
        ->assertNotFound();
});
