<?php

use App\Models\Project;
use App\Models\ProjectDocumentVersion;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function projectManager(): User
{
    Permission::firstOrCreate(['name' => 'projects.manage', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->givePermissionTo('projects.manage');

    return $user;
}

test('an admin can add a project document and it is recorded in history', function () {
    Storage::fake('public');
    $user = projectManager();
    $selection = SelectionProcess::factory()->create();
    $project = Project::factory()->create(['selection_process_id' => $selection->id, 'content' => ['documents' => []]]);

    $response = $this->actingAs($user)->post(route('selection.projects.documents.upload', [$selection, $project]), [
        'label' => 'Projeto de pesquisa',
        'file' => UploadedFile::fake()->create('projeto.pdf', 120, 'application/pdf'),
    ]);

    $response->assertRedirect();
    $project->refresh();
    $document = $project->content['documents'][0];
    expect(ProjectDocumentVersion::count())->toBe(1)
        ->and($document['name'])->toBe('projeto.pdf');
    Storage::disk('public')->assertExists($document['path']);
});

test('replacing a project document preserves the previous file and records both versions', function () {
    Storage::fake('public');
    $user = projectManager();
    $selection = SelectionProcess::factory()->create();
    $oldPath = 'imports/old.pdf';
    Storage::disk('public')->put($oldPath, 'old content');
    $project = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'content' => ['documents' => [['label' => 'Projeto', 'name' => 'old.pdf', 'filename' => 'old.pdf', 'path' => $oldPath]]],
    ]);

    $this->actingAs($user)->post(route('selection.projects.documents.upload', [$selection, $project]), [
        'label' => 'Projeto',
        'document_index' => '0',
        'file' => UploadedFile::fake()->create('new.pdf', 100, 'application/pdf'),
    ])->assertRedirect();

    $project->refresh();
    expect(ProjectDocumentVersion::where('project_id', $project->id)->count())->toBe(2)
        ->and($project->content['documents'][0]['name'])->toBe('new.pdf');
    Storage::disk('public')->assertExists($oldPath);
});

test('adding a project document appends it instead of replacing the first document', function () {
    Storage::fake('public');
    $user = projectManager();
    $selection = SelectionProcess::factory()->create();
    $project = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'content' => ['documents' => [['label' => 'Existente', 'name' => 'old.pdf', 'filename' => 'old.pdf', 'path' => 'imports/old.pdf']]],
    ]);

    $this->actingAs($user)->post(route('selection.projects.documents.upload', [$selection, $project]), [
        'label' => 'Novo documento',
        'document_index' => null,
        'file' => UploadedFile::fake()->create('new.pdf', 100, 'application/pdf'),
    ])->assertRedirect();

    $project->refresh();
    expect($project->content['documents'])->toHaveCount(2)
        ->and($project->content['documents'][0]['name'])->toBe('old.pdf')
        ->and($project->content['documents'][1]['name'])->toBe('new.pdf');
});
