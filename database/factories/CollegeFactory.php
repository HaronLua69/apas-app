<?php

namespace Database\Factories;

use App\Models\College;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<College>
 */
class CollegeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'College of '.fake()->unique()->words(2, true),
            'abbreviation' => strtoupper(fake()->lexify('???')),
            'dean' => fake()->name(),
            'description' => fake()->sentence(),
        ];
    }
}
