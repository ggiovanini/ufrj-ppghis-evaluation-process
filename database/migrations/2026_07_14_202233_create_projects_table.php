<?php

use App\Domain\Projects\Types\ProjectStage;
use App\Domain\Review\Types\ReviewStatus;
use App\Models\Project;
use App\Models\ReviewAssignment;
use App\Models\ReviewForm;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(SelectionProcess::class)
                ->constrained()->cascadeOnDelete();
            $table->string('candidate_name');
            $table->string('title');
            $table->string('modality');
            $table->json('original_content');
            $table->json('content')->nullable();
            $table->string('stage')
                ->default(ProjectStage::IMPORTED->value);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('review_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Project::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->boolean('chosen_by_candidate')->default(false);
            $table->timestamp('assigned_at')->nullable();

            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(ReviewAssignment::class)
                ->unique()
                ->constrained()->cascadeOnDelete();
            $table->foreignIdFor(ReviewForm::class)->constrained()->cascadeOnDelete();
            $table->string('status')->default(ReviewStatus::DRAFT->value);
            $table->integer('score')->nullable();
            $table->json('answers')->nullable();
            $table->text('comments')->nullable();
            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();
        });

        Schema::create('written_exams', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Project::class)
                ->unique()
                ->constrained()->cascadeOnDelete();
            $table->integer('score')->nullable();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->timestamp('recorded_at')->nullable();

            $table->timestamps();
        });

        Schema::create('committee_evaluations', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Project::class)
                ->unique()
                ->constrained()->cascadeOnDelete();
            $table->integer('score')->nullable();
            $table->text('comments')->nullable();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();
        });

        Schema::create('final_results', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Project::class)
                ->unique()
                ->constrained()->cascadeOnDelete();
            $table->float('review_average')->nullable();
            $table->integer('written_exam_score')->nullable();
            $table->integer('committee_score')->nullable();
            $table->integer('final_score')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
        Schema::dropIfExists('review_assignments');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('written_exams');
        Schema::dropIfExists('committee_evaluations');
        Schema::dropIfExists('final_results');
    }
};
