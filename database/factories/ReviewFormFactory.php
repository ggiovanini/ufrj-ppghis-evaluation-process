<?php

namespace Database\Factories;

use App\Models\ReviewForm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewForm>
 */
class ReviewFormFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(2),
            'version' => '1.0',
            'schema' => [],
            'active' => true,
        ];
    }
}
