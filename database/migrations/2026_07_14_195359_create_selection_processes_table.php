<?php

use App\Domain\SelectionProcess\Types\SelectionProcessPhases;
use App\Models\ReviewForm;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('review_forms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('version')->default('1');
            $table->json('schema')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('selection_processes', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('description')->nullable();
            $table->integer('year')->nullable();
            $table->string('phase')
                ->default(SelectionProcessPhases::IMPORT->value);

            $table->foreignIdFor(ReviewForm::class)->constrained();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_forms');
        Schema::dropIfExists('selection_processes');
    }
};
