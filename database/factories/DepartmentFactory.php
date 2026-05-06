<?php

namespace Database\Factories;

use App\Models\College;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'college_id' => College::factory(),
            'name' => 'Department of '.fake()->unique()->words(3, true),
            'abbreviation' => strtoupper(fake()->lexify('????')),
            'chairperson' => fake()->name(),
            'description' => fake()->sentence(),
        ];
    }
}
