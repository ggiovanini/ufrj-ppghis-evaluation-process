<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ReviewAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewAssignment>
 */
class ReviewAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'chosen_by_candidate' => $this->faker->boolean(),
        ];
    }
}
