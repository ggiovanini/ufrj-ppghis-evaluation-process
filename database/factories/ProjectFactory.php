<?php

namespace Database\Factories;

use App\Domain\Projects\Types\ProjectModality;
use App\Domain\Projects\Types\ProjectStage;
use App\Models\Project;
use App\Models\SelectionProcess;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'selection_process_id' => SelectionProcess::factory(),
            'candidate_name' => $this->faker->name(),
            'title' => $this->faker->sentence(),
            'modality' => $this->faker->randomElement(ProjectModality::cases()),
            'stage' => ProjectStage::IMPORTED->value,
        ];
    }
}
