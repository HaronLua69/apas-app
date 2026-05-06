<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'subject_code' => strtoupper(fake()->lexify('???')).fake()->numberBetween(100, 499),
            'subject_title' => fake()->unique()->sentence(3),
            'subject_types' => ['Lecture'],
            'credit_units' => fake()->randomElement([1, 2, 3, 4, 5]),
            'grading_system' => fake()->randomElement(['numerical', 'letter']),
            'description' => fake()->sentence(),
            'counts_toward_gpa' => true,
        ];
    }
}
