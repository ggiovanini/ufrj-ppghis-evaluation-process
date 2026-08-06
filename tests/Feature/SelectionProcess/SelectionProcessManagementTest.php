<?php

namespace Tests\Feature\SelectionProcess;

use App\Models\Project;
use App\Models\ReviewForm;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('it can render create selection process page', function () {
    Permission::create(['name' => 'projects.manage', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->givePermissionTo('projects.manage');

    $response = $this->actingAs($user)
        ->get(route('selection.create'));

    $response->assertOk();
});

test('it can store a selection process', function () {
    Permission::create(['name' => 'projects.manage', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->givePermissionTo('projects.manage');

    $reviewForm = ReviewForm::factory()->create(['active' => true]);

    $response = $this->actingAs($user)
        ->post(route('selection.store'), [
            'name' => 'Processo de Seleção 2026',
            'description' => 'Descrição do processo',
            'year' => 2026,
            'review_form_id' => $reviewForm->id,
        ]);

    $selection = SelectionProcess::first();

    $response->assertRedirect(route('selection.show', $selection));

    $this->assertDatabaseHas('selection_processes', [
        'name' => 'Processo de Seleção 2026',
        'year' => 2026,
        'review_form_id' => $reviewForm->id,
    ]);
});

test('it can render show selection process page', function () {
    Role::firstOrCreate(['name' => 'reviewer', 'guard_name' => 'web']);
    Permission::create(['name' => 'projects.import', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->givePermissionTo('projects.import');

    $selection = SelectionProcess::factory()->create();
    Project::factory()->create(['selection_process_id' => $selection->id]);

    $response = $this->actingAs($user)
        ->get(route('selection.show', $selection));

    $response->assertOk();
});
