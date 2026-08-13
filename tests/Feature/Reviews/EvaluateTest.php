<?php

use App\Domain\Review\Notifications\ReviewPdfReadyNotification;
use App\Domain\Review\Types\ReviewScore;
use App\Domain\Review\Types\ReviewStatus;
use App\Models\Project;
use App\Models\Review;
use App\Models\ReviewForm;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->reviewerRole = Role::firstOrCreate(['name' => 'reviewer', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'review.view-own', 'guard_name' => 'web']);
    $this->reviewerRole->givePermissionTo('review.view-own');

    $this->reviewer = User::factory()->create();
    $this->reviewer->assignRole($this->reviewerRole);

    $this->selection = SelectionProcess::factory()->create();
    $this->projects = Project::factory()->count(3)->create([
        'selection_process_id' => $this->selection->id,
    ]);

    // Atribui 2 projetos ao revisor
    $this->projects[0]->update(['candidate_name' => 'A Candidate']);
    $this->projects[1]->update(['candidate_name' => 'B Candidate']);
    $this->projects[0]->reviewAssignments()->create(['user_id' => $this->reviewer->id]);
    $this->projects[1]->reviewAssignments()->create(['user_id' => $this->reviewer->id]);
});

test('reviewer can see assigned projects', function () {
    $this->actingAs($this->reviewer)
        ->get(route('selection.evaluate', $this->selection))
        ->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->component('reviews/Index')
            ->has('projects.data', 2)
            ->where('projects.data.0.id', $this->projects[0]->id)
            ->where('projects.data.1.id', $this->projects[1]->id)
        );
});

test('reviewer can search projects by candidate name', function () {
    $this->projects[0]->update(['candidate_name' => 'John Doe']);
    $this->projects[1]->update(['candidate_name' => 'Jane Smith']);

    $this->actingAs($this->reviewer)
        ->get(route('selection.evaluate', [$this->selection, 'search' => 'John']))
        ->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->component('reviews/Index')
            ->has('projects.data', 1)
            ->where('projects.data.0.candidate_name', 'John Doe')
        );
});

test('reviewer can sort projects by candidate name', function () {
    $this->projects[0]->update(['candidate_name' => 'Zebra']);
    $this->projects[1]->update(['candidate_name' => 'Apple']);

    // Ascending
    $this->actingAs($this->reviewer)
        ->get(route('selection.evaluate', [$this->selection, 'sort' => 'candidate_name', 'direction' => 'asc']))
        ->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->component('reviews/Index')
            ->where('projects.data.0.candidate_name', 'Apple')
            ->where('projects.data.1.candidate_name', 'Zebra')
        );

    // Descending
    $this->actingAs($this->reviewer)
        ->get(route('selection.evaluate', [$this->selection, 'sort' => 'candidate_name', 'direction' => 'desc']))
        ->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->component('reviews/Index')
            ->where('projects.data.0.candidate_name', 'Zebra')
            ->where('projects.data.1.candidate_name', 'Apple')
        );
});

test('reviewer can sort projects by status', function () {
    $p1 = $this->projects[0];
    $p2 = $this->projects[1];

    // p1 has no review (Pendente -> 1)
    // p2 has a draft review (Rascunho -> 2)
    $assignment2 = $p2->reviewAssignments()->where('user_id', $this->reviewer->id)->first();
    Review::factory()->create([
        'review_assignment_id' => $assignment2->id,
        'status' => ReviewStatus::DRAFT,
    ]);

    $this->actingAs($this->reviewer)
        ->get(route('selection.evaluate', [$this->selection, 'sort' => 'status', 'direction' => 'asc']))
        ->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->component('reviews/Index')
            ->where('projects.data.0.id', $p1->id) // Pendente first
            ->where('projects.data.1.id', $p2->id) // Rascunho second
        );

    $this->actingAs($this->reviewer)
        ->get(route('selection.evaluate', [$this->selection, 'sort' => 'status', 'direction' => 'desc']))
        ->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->component('reviews/Index')
            ->where('projects.data.0.id', $p2->id) // Rascunho first
            ->where('projects.data.1.id', $p1->id) // Pendente second
        );
});

test('unauthorized user cannot see evaluate page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('selection.evaluate', $this->selection))
        ->assertStatus(403);
});

test('reviewer can save evaluation as draft', function () {
    $project = $this->projects[0];

    $this->actingAs($this->reviewer)
        ->post(route('selection.evaluate.store', [$this->selection, $project]), [
            'score' => ReviewScore::APPROVED->value,
            'comments' => 'Good project draft',
            'status' => 'draft',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Rascunho salvo com sucesso!');

    $this->assertDatabaseHas('reviews', [
        'score' => ReviewScore::APPROVED->value,
        'comments' => 'Good project draft',
        'status' => ReviewStatus::DRAFT->value,
    ]);
});

test('reviewer can save evaluation with answers', function () {
    $project = $this->projects[0];
    $answers = [
        '1' => 'Sim',
        '2' => 'Não',
        '8' => 'Algum comentário detalhado',
    ];

    $this->actingAs($this->reviewer)
        ->post(route('selection.evaluate.store', [$this->selection, $project]), [
            'score' => ReviewScore::APPROVED_WITH_RESERVATIONS->value,
            'comments' => 'Project with answers',
            'status' => 'draft',
            'answers' => $answers,
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Rascunho salvo com sucesso!');

    $review = Review::whereHas('reviewAssignment', fn ($q) => $q->where('project_id', $project->id))->first();
    expect($review->answers)->toBe($answers);
});

test('reviewer can submit evaluation', function () {
    $project = $this->projects[0];

    $this->actingAs($this->reviewer)
        ->post(route('selection.evaluate.store', [$this->selection, $project]), [
            'score' => ReviewScore::APPROVED->value,
            'comments' => 'Excellent project',
            'status' => 'submitted',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Avaliação enviada com sucesso!');

    $this->assertDatabaseHas('reviews', [
        'score' => ReviewScore::APPROVED->value,
        'comments' => 'Excellent project',
        'status' => ReviewStatus::SUBMITTED->value,
    ]);

    $review = Review::whereHas('reviewAssignment', fn ($q) => $q->where('project_id', $project->id))->first();
    expect($review->submitted_at)->not->toBeNull();
});

test('submitting evaluation generates and stores its pdf and notifies reviewer', function () {
    Storage::fake('local');
    Notification::fake();

    $project = $this->projects[0];

    $this->actingAs($this->reviewer)
        ->post(route('selection.evaluate.store', [$this->selection, $project]), [
            'score' => ReviewScore::APPROVED->value,
            'comments' => 'Excellent project',
            'status' => ReviewStatus::SUBMITTED->value,
        ])
        ->assertRedirect();

    $review = Review::whereHas('reviewAssignment', fn ($query) => $query->where('project_id', $project->id))->firstOrFail();

    expect($review->pdf_path)->toBe('reviews/'.$review->id.'.pdf');
    Storage::disk('local')->assertExists($review->pdf_path);
    Notification::assertSentTo($this->reviewer, ReviewPdfReadyNotification::class);
});

test('reviewer can download their stored evaluation pdf', function () {
    Storage::fake('local');
    $review = Review::factory()->create([
        'review_assignment_id' => $this->projects[0]->reviewAssignments()->first()->id,
        'status' => ReviewStatus::SUBMITTED,
        'score' => ReviewScore::APPROVED,
        'pdf_path' => 'reviews/1.pdf',
    ]);
    Storage::disk('local')->put($review->pdf_path, 'pdf-content');

    $this->actingAs($this->reviewer)
        ->get(route('selection.reviews.pdf', [$this->selection, $review]))
        ->assertOk()
        ->assertDownload('avaliacao-'.$review->id.'.pdf');
});

test('reviewer cannot edit submitted evaluation', function () {
    $project = $this->projects[0];
    $assignment = $project->reviewAssignments()->where('user_id', $this->reviewer->id)->first();
    Review::factory()->create([
        'review_assignment_id' => $assignment->id,
        'status' => ReviewStatus::SUBMITTED,
        'score' => ReviewScore::APPROVED->value,
    ]);

    $this->actingAs($this->reviewer)
        ->post(route('selection.evaluate.store', [$this->selection, $project]), [
            'score' => ReviewScore::DISAPPROVED->value,
            'comments' => 'Trying to change',
            'status' => 'draft',
        ])
        ->assertRedirect()
        ->assertSessionHas('error', 'Esta avaliação já foi submetida e não pode ser alterada.');

    $this->assertDatabaseHas('reviews', [
        'review_assignment_id' => $assignment->id,
        'status' => ReviewStatus::SUBMITTED->value,
        'score' => ReviewScore::APPROVED->value,
    ]);
});

test('evaluation requires valid score', function () {
    $project = $this->projects[0];

    $this->actingAs($this->reviewer)
        ->post(route('selection.evaluate.store', [$this->selection, $project]), [
            'score' => 101,
            'comments' => 'Invalid score',
            'status' => 'draft',
        ])
        ->assertSessionHasErrors('score');
});

test('reviewer cannot submit evaluation without score', function () {
    $project = $this->projects[0];

    $this->actingAs($this->reviewer)
        ->post(route('selection.evaluate.store', [$this->selection, $project]), [
            'score' => null,
            'status' => 'submitted',
        ])
        ->assertSessionHasErrors('score');
});

test('reviewer cannot submit evaluation with pendent score', function () {
    $project = $this->projects[0];

    $this->actingAs($this->reviewer)
        ->post(route('selection.evaluate.store', [$this->selection, $project]), [
            'score' => 0, // PENDENT
            'status' => 'submitted',
        ])
        ->assertSessionHasErrors('score');
});

test('reviewer cannot submit evaluation with missing required answers', function () {
    $project = $this->projects[0];

    // Configurar um formulário com campo obrigatório
    $form = ReviewForm::factory()->create([
        'schema' => [
            'fields' => [
                [
                    'id' => 1,
                    'label' => 'Required Field',
                    'type' => 'text',
                    'required' => true,
                ],
            ],
        ],
    ]);
    $this->selection->update(['review_form_id' => $form->id]);

    $this->actingAs($this->reviewer)
        ->post(route('selection.evaluate.store', [$this->selection, $project]), [
            'score' => ReviewScore::APPROVED->value,
            'status' => 'submitted',
            'answers' => [], // Missing answer for field 1
        ])
        ->assertSessionHasErrors('answers.1');
});
