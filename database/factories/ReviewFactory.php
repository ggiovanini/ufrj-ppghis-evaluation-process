<?php

namespace Database\Factories;

use App\Domain\Review\Types\ReviewScore;
use App\Domain\Review\Types\ReviewStatus;
use App\Models\Review;
use App\Models\ReviewAssignment;
use App\Models\ReviewForm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'review_assignment_id' => ReviewAssignment::factory(),
            'review_form_id' => ReviewForm::factory(),
            'status' => ReviewStatus::PENDENT,
            'score' => ReviewScore::PENDENT,
            'answers' => [],
            'comments' => $this->faker->paragraph(),
            'questions' => $this->faker->paragraph(),
            'submitted_at' => null,
        ];
    }
}
