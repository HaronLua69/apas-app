<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\User;
use App\Models\AdviserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdviserProfile>
 */
class AdviserProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->adviser(),
            'department_id' => Department::factory(),
            'sex_at_birth' => fake()->randomElement(['Male', 'Female']),
            'rank' => fake()->randomElement(['Instructor I', 'Instructor II', 'Assistant Professor I']),
            'home_address' => fake()->address(),
        ];
    }
}
