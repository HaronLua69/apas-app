<?php

use App\Models\AcademicTerm;
use App\Models\AdviserProfile;
use App\Models\College;
use App\Models\Course;
use App\Models\Department;
use App\Models\Major;
use App\Models\StudentAdviserBinding;
use App\Models\StudentProfile;
use App\Models\StudentSubjectEnrollment;
use App\Models\Subject;
use App\Models\User;
use App\Support\ApapReportBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('advisers can open the apap report and receive end-of-academic-year metrics', function () {
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create([
        'name' => 'Bachelor of Science in Information Technology',
    ]);
    $major = Major::factory()->for($course)->create([
        'name' => 'Software Engineering',
    ]);
    $otherCollege = College::factory()->create();
    $otherDepartment = Department::factory()->for($otherCollege)->create();
    $otherCourse = Course::factory()->for($otherDepartment)->create([
        'name' => 'Bachelor of Science in Computer Science',
    ]);

    AcademicTerm::factory()->create([
        'academic_year_start' => 2024,
        'term_name' => '1st Semester',
        'date_from' => '2024-08-01',
        'date_to' => '2024-12-20',
    ]);
    AcademicTerm::factory()->create([
        'academic_year_start' => 2024,
        'term_name' => '2nd Semester',
        'date_from' => '2025-01-10',
        'date_to' => '2025-05-31',
    ]);
    AcademicTerm::factory()->create([
        'academic_year_start' => 2024,
        'term_name' => 'Summer Term',
        'date_from' => '2025-06-01',
        'date_to' => '2025-07-15',
    ]);
    AcademicTerm::factory()->create([
        'academic_year_start' => 2025,
        'term_name' => '1st Semester',
        'date_from' => now()->subWeek()->toDateString(),
        'date_to' => now()->addWeek()->toDateString(),
    ]);

    $subjects = collect([
        Subject::factory()->for($department)->create(['subject_code' => 'AP101', 'credit_units' => 3]),
        Subject::factory()->for($department)->create(['subject_code' => 'AP102', 'credit_units' => 3]),
        Subject::factory()->for($department)->create(['subject_code' => 'AP103', 'credit_units' => 3]),
        Subject::factory()->for($department)->create(['subject_code' => 'AP104', 'credit_units' => 3]),
    ]);

    $adviser = User::factory()->adviser()->create();
    AdviserProfile::factory()->for($adviser)->for($department)->create();
    $assignment = $adviser->adviserProfile->adviserAssignments()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'year_level' => 2,
    ]);

    $createAdvisee = function (string $firstName, int $currentYearLevel, ?string $withdrawnAt = null) use ($assignment, $course, $major): StudentProfile {
        $student = User::factory()->create([
            'first_name' => $firstName,
            'last_name' => 'Student',
            'name' => $firstName.' Student',
            'id_number' => fake()->unique()->numerify('2022-####'),
        ]);

        $profile = StudentProfile::factory()->for($student)->create([
            'year_level' => $currentYearLevel,
            'withdrawn_at' => $withdrawnAt,
        ]);

        $profile->admissions()->create([
            'course_id' => $course->id,
            'major_id' => $major->id,
            'admission_date' => '2024-06-01',
            'is_active' => true,
        ]);

        StudentAdviserBinding::query()->create([
            'student_profile_id' => $profile->id,
            'adviser_assignment_id' => $assignment->id,
        ]);

        return $profile;
    };

    $recordEnrollment = function (StudentProfile $profile, Subject $subject, string $schoolYear, int $yearLevel, string $termName, string $grade, array $attributes = []): void {
        StudentSubjectEnrollment::query()->create([
            'student_profile_id' => $profile->id,
            'subject_id' => $subject->id,
            'school_year' => $schoolYear,
            'year_level' => $yearLevel,
            'term_name' => $termName,
            'attempt_number' => $attributes['attempt_number'] ?? 1,
            'status' => $attributes['status'] ?? 'confirmed',
            'grade' => $grade,
            'confirmed_at' => $attributes['confirmed_at'] ?? now(),
            'completion_grade' => $attributes['completion_grade'] ?? null,
            'completion_confirmed_at' => $attributes['completion_confirmed_at'] ?? null,
        ]);
    };

    $alpha = $createAdvisee('Alpha', 3);
    $beta = $createAdvisee('Beta', 3);
    $gamma = $createAdvisee('Gamma', 3);
    $delta = $createAdvisee('Delta', 2, '2025-05-10 09:00:00');

    $recordEnrollment($alpha, $subjects[0], '2024-2025', 2, '1st Semester', '1.25');
    $recordEnrollment($alpha, $subjects[1], '2024-2025', 2, '2nd Semester', '1.00');
    $recordEnrollment($alpha, $subjects[2], '2025-2026', 3, '1st Semester', '1.50');

    $recordEnrollment($beta, $subjects[0], '2024-2025', 2, '1st Semester', 'INC', [
        'completion_grade' => '1.50',
        'completion_confirmed_at' => now()->setDate(2025, 3, 15),
    ]);
    $recordEnrollment($beta, $subjects[1], '2024-2025', 2, '2nd Semester', '1.50');
    $recordEnrollment($beta, $subjects[2], '2024-2025', 2, 'Summer Term', '1.00');
    $recordEnrollment($beta, $subjects[3], '2025-2026', 3, '1st Semester', '1.75');

    $recordEnrollment($gamma, $subjects[0], '2024-2025', 2, '1st Semester', '1.75');
    $recordEnrollment($gamma, $subjects[1], '2024-2025', 2, '2nd Semester', '1.60');
    $recordEnrollment($gamma, $subjects[2], '2025-2026', 3, '1st Semester', '2.00');

    $recordEnrollment($delta, $subjects[0], '2024-2025', 2, '1st Semester', 'INC');
    $recordEnrollment($delta, $subjects[1], '2024-2025', 2, '2nd Semester', '5.00');
    $recordEnrollment($delta, $subjects[2], '2024-2025', 2, '2nd Semester', 'DRP');

    foreach (range(1, 5) as $index) {
        $previousOnly = $createAdvisee('Previous'.$index, 3);
        $recordEnrollment($previousOnly, $subjects[0], '2023-2024', 2, '2nd Semester', '2.00');
    }

    $shifted = $createAdvisee('Shifted', 3);
    $shifted->admissions()->where('is_active', true)->update(['is_active' => false]);
    $shifted->admissions()->create([
        'course_id' => $otherCourse->id,
        'major_id' => null,
        'admission_date' => '2025-04-15',
        'is_active' => true,
    ]);
    $recordEnrollment($shifted, $subjects[0], '2023-2024', 2, '2nd Semester', '2.25');

    $report = app(ApapReportBuilder::class)->build($adviser, [
        'academic_year' => '2024-2025',
        'course_id' => $course->id,
        'major_id' => $major->id,
        'year_level' => 2,
    ]);

    expect($report['report']['totalProgramEnrollees'])->toBe(4)
        ->and($report['report']['completionRate'])->toBe(75.0)
        ->and($report['report']['promotionRate'])->toBe(75.0)
        ->and($report['report']['failureRate'])->toBe(25.0)
        ->and($report['report']['dropoutRate'])->toBe(25.0)
        ->and($report['report']['averageAcademicYearGpa'])->toBe(2.28333)
        ->and($report['report']['averageCumulativeGpa'])->toBe(2.36771)
        ->and($report['report']['studentsWithInc'])->toBe(1)
        ->and($report['report']['studentsWithdrawn'])->toBe(1)
        ->and($report['report']['studentsWithFailingGrades'])->toBe(1)
        ->and($report['report']['rizalAwardees'])->toBe(1)
        ->and($report['report']['chancellorAwardees'])->toBe(1)
        ->and($report['report']['deanAwardees'])->toBe(1);

    $cohortDisplayNames = collect($report['report']['rows'])
        ->map(fn (array $row): string => $row['displayName'])
        ->all();

    $this->actingAs($adviser)
        ->get(route('adviser.reports.apap', [
            'academic_year' => '2024-2025',
            'course_id' => $course->id,
            'major_id' => $major->id,
            'year_level' => 2,
        ]))
        ->assertOk()
        ->assertSee('APAP Report')
        ->assertSee('Total Program Enrollees');

    foreach ($cohortDisplayNames as $displayName) {
        $this->actingAs($adviser)
            ->get(route('adviser.reports.apap', [
                'academic_year' => '2024-2025',
                'course_id' => $course->id,
                'major_id' => $major->id,
                'year_level' => 2,
            ]))
            ->assertSee($displayName);
    }

    $exportResponse = $this->actingAs($adviser)
        ->get(route('adviser.reports.apap.export', [
            'academic_year' => '2024-2025',
            'course_id' => $course->id,
            'major_id' => $major->id,
            'year_level' => 2,
        ]));

    $exportResponse
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $exportResponse->streamedContent();

    expect($csv)->toContain('Academic Program Advising Progress (APAP) Report')
        ->toContain('"Total Program Enrollees",4')
        ->toContain('"Completion Rate",75.00%');

    foreach ($cohortDisplayNames as $displayName) {
        expect($csv)->toContain($displayName);
    }
});
