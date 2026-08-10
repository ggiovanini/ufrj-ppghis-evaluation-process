<?php

use App\Domain\Projects\Types\ProjectScore;

test('it converts a score with two decimal places to its integer representation', function () {
    $score = new ProjectScore('10.00');

    expect($score->value())->toBe(1000)
        ->and($score->format())->toBe('10,00');
});

test('it formats a stored score in centesimal representation', function () {
    expect(ProjectScore::make(1000)->format())->toBe('10,00');
});

test('it preserves the existing score scale for decimal values', function () {
    expect(new ProjectScore('9.99')->value())->toBe(999)
        ->and(new ProjectScore('3.5')->value())->toBe(350);
});
