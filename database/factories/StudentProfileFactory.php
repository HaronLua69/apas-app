<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\StudentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'sex_at_birth' => fake()->randomElement(['Male', 'Female']),
            'year_level' => fake()->numberBetween(1, 4),
            'home_address' => fake()->address(),
            'is_graduating' => false,
        ];
    }
}
