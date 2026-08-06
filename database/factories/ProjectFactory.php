<?php

namespace Database\Factories;

use App\Domain\Projects\Types\ProjectHomologationStatus;
use App\Domain\Projects\Types\ProjectModality;
use App\Domain\Projects\Types\ProjectStage;
use App\Models\Project;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'selection_process_id' => SelectionProcess::factory(),
            'register_id' => $this->faker->numberBetween(1001, 2000),
            'candidate_name' => $this->faker->name(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->text(400),
            'indication' => User::inRandomOrder()->first()?->name ?? $this->faker->name(),
            'modality' => $this->faker->randomElement(ProjectModality::cases()),
            'stage' => ProjectStage::IMPORTED->value,
            'homologation_status' => ProjectHomologationStatus::APPROVED->value,
            'review_score' => null,
            'written_exam_score' => null,
            'committee_score' => null,
            'original_content' => [],
        ];
    }
}
