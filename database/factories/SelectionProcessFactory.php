<?php

namespace Database\Factories;

use App\Models\ReviewForm;
use App\Models\SelectionProcess;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SelectionProcess>
 */
class SelectionProcessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'year' => $this->faker->year,
            'review_form_id' => ReviewForm::factory(),
        ];
    }
}
