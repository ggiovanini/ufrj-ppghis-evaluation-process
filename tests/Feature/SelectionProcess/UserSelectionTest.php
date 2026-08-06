<?php

use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it automatically selects the most recent selection process for a new user', function () {
    $oldProcess = SelectionProcess::factory()->create(['year' => 2024]);
    $newProcess = SelectionProcess::factory()->create(['year' => 2025]);

    $user = User::factory()->create(['current_selection_process_id' => null]);

    $this->actingAs($user)
        ->get(route('dashboard'));

    $user->refresh();

    expect($user->current_selection_process_id)->toBe($newProcess->id);
});

test('it can change the current selection process', function () {
    $process1 = SelectionProcess::factory()->create(['year' => 2024]);
    $process2 = SelectionProcess::factory()->create(['year' => 2025]);

    $user = User::factory()->create(['current_selection_process_id' => $process1->id]);

    $response = $this->actingAs($user)
        ->post(route('selection.select'), [
            'selection_process_id' => $process2->id,
        ]);

    $response->assertRedirect();
    $user->refresh();

    expect($user->current_selection_process_id)->toBe($process2->id);
});

test('it shares selection processes with inertia', function () {
    $process = SelectionProcess::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('selectionProcesses')
            ->has('auth.currentSelectionProcess')
        );
});
