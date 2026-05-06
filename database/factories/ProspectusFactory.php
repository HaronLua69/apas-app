<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Prospectus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prospectus>
 */
class ProspectusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'major_id' => null,
            'title' => fake()->words(3, true).' Prospectus',
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
