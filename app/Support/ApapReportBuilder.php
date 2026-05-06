<?php

namespace App\Support;

use App\Models\AcademicTerm;
use App\Models\Course;
use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\StudentSubjectEnrollment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ApapReportBuilder
{
    public function __construct(private StudentAcademicView $studentAcademicView)
    {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(User $adviser, array $filters = []): array
    {
        $students = $this->assignedStudents($adviser);
        $academicYearOptions = $this->academicYearOptions();
        $selectedAcademicYear = $this->selectedAcademicYear(
            is_string($filters['academic_year'] ?? null) ? $filters['academic_year'] : null,
            $academicYearOptions,
        );

        $courseOptions = $students
            ->map(fn (StudentProfile $studentProfile) => $studentProfile->activeAdmission?->course)
            ->filter(fn ($course) => $course instanceof Course)
            ->unique('id')
            ->sortBy('name')
            ->values();

        $selectedCourseId = $this->selectedInteger(
            $filters['course_id'] ?? null,
            $courseOptions->pluck('id')->map(fn (int $id) => (int) $id),
        );

        $majorOptions = $students
            ->filter(fn (StudentProfile $studentProfile) => $selectedCourseId === null
                || $studentProfile->admissions->contains(fn ($admission) => (int) $admission->course_id === $selectedCourseId))
            ->map(fn (StudentProfile $studentProfile) => $studentProfile->activeAdmission?->major)
            ->filter(fn ($major) => $major instanceof Major)
            ->unique('id')
            ->sortBy('name')
            ->values();

        $selectedMajorId = $this->selectedInteger(
            $filters['major_id'] ?? null,
            $majorOptions->pluck('id')->map(fn (int $id) => (int) $id),
        );

        $yearLevelOptions = collect([1, 2, 3, 4]);
        $selectedYearLevel = $this->selectedInteger($filters['year_level'] ?? null, $yearLevelOptions);
        $scopedStudents = $students->filter(fn (StudentProfile $studentProfile) => $this->matchesProgramScope(
            $studentProfile,
            $selectedCourseId,
            $selectedMajorId,
        ));

        $cohort = $this->cohortStudents($scopedStudents, $selectedAcademicYear, $selectedYearLevel);
        $previousCohort = $this->cohortStudents(
            $scopedStudents,
            $this->adjacentAcademicYear($selectedAcademicYear, -1),
            $selectedYearLevel,
        );
        $academicYearBounds = $this->academicYearBounds($selectedAcademicYear);
        $snapshots = $cohort
            ->map(fn (StudentProfile $studentProfile) => $this->studentSnapshot(
                $studentProfile,
                $selectedAcademicYear,
                $selectedYearLevel,
                $selectedCourseId,
                $selectedMajorId,
                $academicYearBounds,
            ))
            ->values();

        $denominator = $snapshots->count();
        $completionCount = $snapshots->where('countsAsCompleted', true)->count();
        $promotedCount = $snapshots->where('promoted', true)->count();
        $failureCount = $snapshots->where('failedEndOfYear', true)->count();
        $dropoutCount = $snapshots->where('droppedAnySubject', true)->count();
        $unresolvedIncCount = $snapshots->where('hasUnresolvedInc', true)->count();
        $withdrawnCount = $snapshots->where('withdrewFromProgram', true)->count();
        $failingGradeCount = $snapshots->where('hasFailingGrade', true)->count();

        return [
            'filters' => [
                'academic_year' => $selectedAcademicYear,
                'course_id' => $selectedCourseId,
                'major_id' => $selectedMajorId,
                'year_level' => $selectedYearLevel,
            ],
            'academicYearOptions' => $academicYearOptions,
            'courseOptions' => $courseOptions,
            'majorOptions' => $majorOptions,
            'yearLevelOptions' => $yearLevelOptions,
            'report' => [
                'totalProgramEnrollees' => $denominator,
                'survivalRate' => $previousCohort->isEmpty()
                    ? null
                    : $this->percentage($denominator, $previousCohort->count()),
                'completionRate' => $this->percentage($completionCount, $denominator),
                'promotionRate' => $this->percentage($promotedCount, $denominator),
                'failureRate' => $this->percentage($failureCount, $denominator),
                'dropoutRate' => $this->percentage($dropoutCount, $denominator),
                'averageAcademicYearGpa' => $this->averageFromSnapshots($snapshots, 'academicYearGpa', $denominator),
                'averageCumulativeGpa' => $this->averageFromSnapshots($snapshots, 'cumulativeGpa', $denominator),
                'studentsWithInc' => $unresolvedIncCount,
                'studentsWithdrawn' => $withdrawnCount,
                'studentsWithFailingGrades' => $failingGradeCount,
                'rizalAwardees' => $snapshots->where('award', 'rizal')->count(),
                'chancellorAwardees' => $snapshots->where('award', 'chancellor')->count(),
                'deanAwardees' => $snapshots->where('award', 'dean')->count(),
                'rows' => $snapshots,
            ],
        ];
    }

    /**
     * @return Collection<int, StudentProfile>
     */
    private function assignedStudents(User $adviser): Collection
    {
        return StudentProfile::query()
            ->whereHas('adviserBinding.adviserAssignment.adviserProfile', fn ($query) => $query->where('user_id', $adviser->id))
            ->with([
                'user',
                'activeAdmission.course.department.college',
                'activeAdmission.major',
                'admissions.course.department.college',
                'admissions.major',
                'subjectEnrollments.subject',
                'subjectEnrollments.academicTerm',
            ])
            ->get()
            ->sortBy(fn (StudentProfile $studentProfile) => [
                $studentProfile->user?->last_name,
                $studentProfile->user?->first_name,
            ])
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    private function academicYearOptions(): Collection
    {
        $currentAcademicYearStart = AcademicTerm::active()?->academic_year_start
            ?? AcademicTerm::query()->max('academic_year_start')
            ?? now()->year;

        return collect(range($currentAcademicYearStart - 5, $currentAcademicYearStart))
            ->map(fn (int $academicYearStart) => $academicYearStart.'-'.($academicYearStart + 1))
            ->values();
    }

    private function selectedAcademicYear(?string $academicYear, Collection $options): string
    {
        return $options->contains($academicYear)
            ? $academicYear
            : (string) $options->last();
    }

    private function selectedInteger(mixed $value, Collection $allowedValues): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $integerValue = (int) $value;

        return $allowedValues->contains($integerValue) ? $integerValue : null;
    }

    private function matchesProgramScope(StudentProfile $studentProfile, ?int $courseId, ?int $majorId): bool
    {
        if ($courseId === null) {
            return true;
        }

        return $studentProfile->admissions->contains(function ($admission) use ($courseId, $majorId): bool {
            if ((int) $admission->course_id !== $courseId) {
                return false;
            }

            if ($majorId === null) {
                return true;
            }

            return (int) ($admission->major_id ?? 0) === $majorId;
        });
    }

    /**
     * @return Collection<int, StudentProfile>
     */
    private function cohortStudents(Collection $students, string $academicYear, ?int $yearLevel): Collection
    {
        $endOfYearTerms = $this->endOfYearTerms($students, $academicYear, $yearLevel);

        if ($endOfYearTerms->isEmpty()) {
            return collect();
        }

        return $students
            ->filter(function (StudentProfile $studentProfile) use ($academicYear, $yearLevel, $endOfYearTerms): bool {
                return $this->academicYearEnrollments($studentProfile, $academicYear, $yearLevel)
                    ->contains(fn (StudentSubjectEnrollment $enrollment) => $endOfYearTerms->contains($enrollment->term_name));
            })
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    private function endOfYearTerms(Collection $students, string $academicYear, ?int $yearLevel): Collection
    {
        $terms = $students
            ->flatMap(fn (StudentProfile $studentProfile) => $this->academicYearEnrollments($studentProfile, $academicYear, $yearLevel))
            ->pluck('term_name')
            ->unique()
            ->sortBy(fn (string $termName) => $this->termSortOrder($termName))
            ->values();

        if ($terms->contains('2nd Semester') || $terms->contains('Summer Term')) {
            return $terms->filter(fn (string $termName) => in_array($termName, ['2nd Semester', 'Summer Term'], true))->values();
        }

        return $terms->filter(fn (string $termName) => $termName === '1st Semester')->values();
    }

    /**
     * @return Collection<int, StudentSubjectEnrollment>
     */
    private function academicYearEnrollments(StudentProfile $studentProfile, string $academicYear, ?int $yearLevel): Collection
    {
        return $studentProfile->subjectEnrollments
            ->filter(fn (StudentSubjectEnrollment $enrollment) => $enrollment->school_year === $academicYear
                && ($yearLevel === null || (int) $enrollment->year_level === $yearLevel))
            ->values();
    }

    /**
     * @param  array{start:CarbonImmutable,end:CarbonImmutable}  $academicYearBounds
     * @return array<string, mixed>
     */
    private function studentSnapshot(
        StudentProfile $studentProfile,
        string $academicYear,
        ?int $selectedYearLevel,
        ?int $selectedCourseId,
        ?int $selectedMajorId,
        array $academicYearBounds,
    ): array {
        $academicYearEnrollments = $this->academicYearEnrollments($studentProfile, $academicYear, $selectedYearLevel);
        $secondSemesterEnrollments = $academicYearEnrollments
            ->filter(fn (StudentSubjectEnrollment $enrollment) => $enrollment->term_name === '2nd Semester')
            ->values();
        $summerEnrollments = $academicYearEnrollments
            ->filter(fn (StudentSubjectEnrollment $enrollment) => $enrollment->term_name === 'Summer Term')
            ->values();
        $endOfAcademicYearGpa = $summerEnrollments->isNotEmpty()
            ? $this->studentAcademicView->calculateGpa($secondSemesterEnrollments->concat($summerEnrollments)->values())
            : $this->studentAcademicView->calculateGpa($secondSemesterEnrollments);

        if ($endOfAcademicYearGpa === null && $summerEnrollments->isNotEmpty()) {
            $endOfAcademicYearGpa = $this->studentAcademicView->calculateGpa($summerEnrollments);
        }

        return [
            'studentProfile' => $studentProfile,
            'displayName' => $studentProfile->user?->adviserDisplayName() ?? $studentProfile->user?->fullName() ?? 'Unknown Student',
            'academicYearGpa' => $this->studentAcademicView->calculateGpa($academicYearEnrollments),
            'endOfAcademicYearGpa' => $endOfAcademicYearGpa,
            'cumulativeGpa' => $this->studentAcademicView->calculateGpa($studentProfile->subjectEnrollments),
            'countsAsCompleted' => $this->countsAsCompleted($academicYearEnrollments, $academicYearBounds),
            'promoted' => $this->isPromoted($studentProfile, $academicYearEnrollments, $academicYear, $selectedYearLevel),
            'failedEndOfYear' => $endOfAcademicYearGpa !== null && $endOfAcademicYearGpa > 3.0,
            'droppedAnySubject' => $academicYearEnrollments->contains(fn (StudentSubjectEnrollment $enrollment) => $this->isDroppedEnrollment($enrollment)),
            'hasUnresolvedInc' => $academicYearEnrollments->contains(fn (StudentSubjectEnrollment $enrollment) => $this->isUnresolvedInc($enrollment, $academicYearEnrollments, $academicYearBounds)),
            'withdrewFromProgram' => $this->withdrewFromProgram(
                $studentProfile,
                $selectedCourseId,
                $selectedMajorId,
                $academicYearBounds,
            ),
            'hasFailingGrade' => $academicYearEnrollments->contains(fn (StudentSubjectEnrollment $enrollment) => $this->isFailingEnrollment($enrollment)),
            'award' => $this->awardLevel($endOfAcademicYearGpa),
        ];
    }

    /**
     * @param  Collection<int, StudentSubjectEnrollment>  $academicYearEnrollments
     * @param  array{start:CarbonImmutable,end:CarbonImmutable}  $academicYearBounds
     */
    private function countsAsCompleted(Collection $academicYearEnrollments, array $academicYearBounds): bool
    {
        return ! $academicYearEnrollments
            ->filter(fn (StudentSubjectEnrollment $enrollment) => $this->isConfirmedEnrollment($enrollment))
            ->contains(fn (StudentSubjectEnrollment $enrollment) => $this->isUnresolvedInc(
                $enrollment,
                $academicYearEnrollments,
                $academicYearBounds,
            ));
    }

    private function isPromoted(
        StudentProfile $studentProfile,
        Collection $academicYearEnrollments,
        string $academicYear,
        ?int $selectedYearLevel,
    ): bool {
        $currentYearLevel = $selectedYearLevel ?? $academicYearEnrollments->max('year_level');

        if (! is_numeric($currentYearLevel) || (int) $currentYearLevel >= 4) {
            return false;
        }

        $nextAcademicYear = $this->adjacentAcademicYear($academicYear, 1);
        $nextAcademicYearEnrollments = $this->academicYearEnrollments($studentProfile, $nextAcademicYear, null);

        if ($nextAcademicYearEnrollments->contains(fn (StudentSubjectEnrollment $enrollment) => (int) $enrollment->year_level === ((int) $currentYearLevel + 1))) {
            return true;
        }

        return (int) $studentProfile->year_level > (int) $currentYearLevel;
    }

    private function isConfirmedEnrollment(StudentSubjectEnrollment $enrollment): bool
    {
        $status = trim((string) $enrollment->status);

        if ($status !== '') {
            return $status === 'confirmed';
        }

        return filled($enrollment->grade) || filled($enrollment->confirmed_at);
    }

    private function isDroppedEnrollment(StudentSubjectEnrollment $enrollment): bool
    {
        if (! $this->isConfirmedEnrollment($enrollment)) {
            return false;
        }

        return in_array(Str::lower(trim((string) $enrollment->grade)), ['drp', 'dropped'], true);
    }

    private function isFailingEnrollment(StudentSubjectEnrollment $enrollment): bool
    {
        if (! $this->isConfirmedEnrollment($enrollment)) {
            return false;
        }

        $grade = trim((string) $enrollment->grade);

        if ($grade === '') {
            return false;
        }

        if (is_numeric($grade)) {
            return (float) $grade > 3.0;
        }

        return in_array(Str::lower($grade), ['f', 'failed'], true);
    }

    /**
     * @param  Collection<int, StudentSubjectEnrollment>  $academicYearEnrollments
     * @param  array{start:CarbonImmutable,end:CarbonImmutable}  $academicYearBounds
     */
    private function isUnresolvedInc(
        StudentSubjectEnrollment $enrollment,
        Collection $academicYearEnrollments,
        array $academicYearBounds,
    ): bool {
        if (! $this->isConfirmedEnrollment($enrollment)) {
            return false;
        }

        if (! in_array(Str::lower(trim((string) $enrollment->grade)), ['inc', 'incomplete'], true)) {
            return false;
        }

        if ($enrollment->completion_confirmed_at !== null
            && $enrollment->completion_grade !== null
            && $enrollment->completion_confirmed_at->betweenIncluded($academicYearBounds['start'], $academicYearBounds['end'])) {
            return false;
        }

        return ! $academicYearEnrollments->contains(function (StudentSubjectEnrollment $otherEnrollment) use ($enrollment): bool {
            if ($otherEnrollment->id === $enrollment->id || $otherEnrollment->subject_id !== $enrollment->subject_id) {
                return false;
            }

            return $this->studentAcademicView->isPassingEnrollment($otherEnrollment);
        });
    }

    /**
     * @param  array{start:CarbonImmutable,end:CarbonImmutable}  $academicYearBounds
     */
    private function withdrewFromProgram(
        StudentProfile $studentProfile,
        ?int $selectedCourseId,
        ?int $selectedMajorId,
        array $academicYearBounds,
    ): bool {
        if ($studentProfile->withdrawn_at !== null
            && $studentProfile->withdrawn_at->betweenIncluded($academicYearBounds['start'], $academicYearBounds['end'])) {
            return true;
        }

        if ($selectedCourseId === null) {
            return false;
        }

        $matchingHistoricalAdmission = $studentProfile->admissions
            ->filter(fn ($admission) => ! $admission->is_active)
            ->first(function ($admission) use ($selectedCourseId, $selectedMajorId, $academicYearBounds): bool {
                if ((int) $admission->course_id !== $selectedCourseId) {
                    return false;
                }

                if ($selectedMajorId !== null && (int) ($admission->major_id ?? 0) !== $selectedMajorId) {
                    return false;
                }

                return $admission->admission_date !== null && $admission->admission_date->lte($academicYearBounds['end']);
            });

        $activeAdmission = $studentProfile->activeAdmission;

        if ($matchingHistoricalAdmission === null || $activeAdmission === null || $activeAdmission->admission_date === null) {
            return false;
        }

        if ($activeAdmission->admission_date->gt($academicYearBounds['end'])) {
            return false;
        }

        $historicalDepartmentId = $matchingHistoricalAdmission->course?->department_id;
        $activeDepartmentId = $activeAdmission->course?->department_id;
        $historicalCollegeId = $matchingHistoricalAdmission->course?->department?->college_id;
        $activeCollegeId = $activeAdmission->course?->department?->college_id;

        return $historicalDepartmentId !== null
            && $activeDepartmentId !== null
            && ($historicalDepartmentId !== $activeDepartmentId || $historicalCollegeId !== $activeCollegeId);
    }

    private function awardLevel(?float $endOfAcademicYearGpa): ?string
    {
        if ($endOfAcademicYearGpa === null) {
            return null;
        }

        return match (true) {
            $endOfAcademicYearGpa <= 1.125 => 'rizal',
            $endOfAcademicYearGpa <= 1.375 => 'chancellor',
            $endOfAcademicYearGpa <= 1.625 => 'dean',
            default => null,
        };
    }

    private function percentage(int $value, int $denominator): ?float
    {
        if ($denominator <= 0) {
            return null;
        }

        return round(($value / $denominator) * 100, 2);
    }

    private function averageFromSnapshots(Collection $snapshots, string $key, int $denominator): ?float
    {
        if ($denominator <= 0) {
            return null;
        }

        return round(
            (float) $snapshots->sum(fn (array $snapshot): float => (float) ($snapshot[$key] ?? 0.0)) / $denominator,
            5,
        );
    }

    private function adjacentAcademicYear(string $academicYear, int $offset): string
    {
        [$startYear] = explode('-', $academicYear);
        $nextStartYear = ((int) $startYear) + $offset;

        return $nextStartYear.'-'.($nextStartYear + 1);
    }

    /**
     * @return array{start:CarbonImmutable,end:CarbonImmutable}
     */
    private function academicYearBounds(string $academicYear): array
    {
        [$startYear] = explode('-', $academicYear);
        $academicYearStart = (int) $startYear;
        $terms = AcademicTerm::query()
            ->where('academic_year_start', $academicYearStart)
            ->orderBy('date_from')
            ->get();

        if ($terms->isNotEmpty()) {
            return [
                'start' => CarbonImmutable::parse($terms->min('date_from')),
                'end' => CarbonImmutable::parse($terms->max('date_to')),
            ];
        }

        return [
            'start' => CarbonImmutable::create($academicYearStart, 8, 1)->startOfDay(),
            'end' => CarbonImmutable::create($academicYearStart + 1, 7, 31)->endOfDay(),
        ];
    }

    private function termSortOrder(string $termName): int
    {
        return match ($termName) {
            '1st Semester' => 1,
            '2nd Semester' => 2,
            'Summer Term' => 3,
            default => 99,
        };
    }
}
