<?php

namespace Database\Factories;

use App\Models\AcademicTerm;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicTerm>
 */
class AcademicTermFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $academicYearStart = fake()->numberBetween(2024, 2027);
        $termName = fake()->randomElement(AcademicTerm::TERM_OPTIONS);

        [$dateFrom, $dateTo] = match ($termName) {
            '1st Semester' => [
                CarbonImmutable::create($academicYearStart, 8, 1),
                CarbonImmutable::create($academicYearStart, 12, 31),
            ],
            '2nd Semester' => [
                CarbonImmutable::create($academicYearStart + 1, 1, 1),
                CarbonImmutable::create($academicYearStart + 1, 5, 31),
            ],
            default => [
                CarbonImmutable::create($academicYearStart + 1, 6, 1),
                CarbonImmutable::create($academicYearStart + 1, 7, 15),
            ],
        };

        return [
            'academic_year_start' => $academicYearStart,
            'term_name' => $termName,
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
        ];
    }
}
