<?php

namespace App\Support;

use App\Enums\ElectiveCategory;
use App\Models\AcademicTerm;
use App\Models\EvaluationTemplate;
use App\Models\Prospectus;
use App\Models\ProspectusTerm;
use App\Models\StudentProfile;
use App\Models\StudentSubjectEnrollment;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StudentAcademicView
{
    /**
     * @return array{
     *     prospectus:?Prospectus,
     *     evaluationTemplate:?EvaluationTemplate,
     *     enrollments:Collection<int, StudentSubjectEnrollment>,
    *     enrollmentIndex:Collection<string, StudentSubjectEnrollment>,
    *     latestEnrollmentBySubject:Collection<int, StudentSubjectEnrollment>,
    *     attemptsBySubject:Collection<int, Collection<int, StudentSubjectEnrollment>>,
     *     evaluationSummary:array<string, int>,
     *     eligibleElectiveSubjectsByTerm:Collection<int, Collection<int, Subject>>,
    *     electivePlacementIndex:Collection<int, Collection<int, array{number:int,elective:\App\Models\ProspectusTermElective,fulfilledBy:?StudentSubjectEnrollment,prerequisites:string,corequisites:string}>>,
     *     completedEnrollmentBySubject:Collection<int, StudentSubjectEnrollment>,
     *     completedSubjectIds:Collection<int, int>,
    *     programOfStudy:Collection<int, array<string, mixed>>,
    *     programOfStudySummary:array{cumulativeGpa:?float},
     *     dashboard:array<string, mixed>,
     *     evaluationProgress:Collection<int, array<string, mixed>>
     * }
     */
    public function build(StudentProfile $studentProfile): array
    {
        $studentProfile->load([
            'user',
            'activeAdmission.course.department.college',
            'activeAdmission.major',
            'subjectEnrollments.subject.requisites.requisiteSubject',
            'subjectEnrollments.subject.electiveScopes',
            'subjectEnrollments.recordedBy',
        ]);

        $activeAdmission = $studentProfile->activeAdmission;
        $prospectus = $this->matchedProspectus($studentProfile);
        $evaluationTemplate = $this->matchedEvaluationTemplate($studentProfile);

        $enrollments = $studentProfile->subjectEnrollments
            ->sortBy(fn (StudentSubjectEnrollment $enrollment) => sprintf(
                '%s-%04d-%02d-%s-%09d',
                $enrollment->school_year,
                $enrollment->year_level,
                $this->termSortOrder($enrollment->term_name),
                $enrollment->subject->subject_code,
                $enrollment->id,
            ))
            ->values();

        $enrollmentIndex = $enrollments->keyBy(fn (StudentSubjectEnrollment $enrollment) => $this->enrollmentKey(
            $enrollment->subject_id,
            $enrollment->year_level,
            $enrollment->term_name,
        ));
        $latestEnrollmentBySubject = $enrollments
            ->groupBy('subject_id')
            ->map(fn (Collection $subjectEnrollments) => $subjectEnrollments->last())
            ->keyBy('subject_id');
        $attemptsBySubject = $enrollments
            ->groupBy('subject_id')
            ->map(fn (Collection $subjectEnrollments) => $subjectEnrollments->values());

        $eligibleElectiveSubjectsByTerm = $activeAdmission === null || $prospectus === null
            ? collect()
            : $prospectus->terms->mapWithKeys(fn (ProspectusTerm $term) => [
                $term->id => $this->eligibleElectiveSubjects(
                    $studentProfile,
                    $prospectus,
                    $term->electives->pluck('category')->filter()->values(),
                ),
            ]);

        $electivePlacementIndex = $activeAdmission === null || $prospectus === null
            ? collect()
            : $this->electivePlacementIndex(
                $prospectus,
                $enrollments,
                $activeAdmission->course_id,
                $activeAdmission->major_id,
            );

        $completedEnrollmentBySubject = $this->completedEnrollmentBySubject($enrollments);
        $completedSubjectIds = $completedEnrollmentBySubject
            ->keys()
            ->map(fn (int|string $subjectId) => (int) $subjectId)
            ->values();
        $programOfStudy = $this->programOfStudy(
            $studentProfile,
            $prospectus,
            $latestEnrollmentBySubject,
            $attemptsBySubject,
            $electivePlacementIndex,
            $enrollments,
        );

        return [
            'prospectus' => $prospectus,
            'evaluationTemplate' => $evaluationTemplate,
            'enrollments' => $enrollments,
            'enrollmentIndex' => $enrollmentIndex,
            'latestEnrollmentBySubject' => $latestEnrollmentBySubject,
            'attemptsBySubject' => $attemptsBySubject,
            'evaluationSummary' => $this->evaluationSummary($evaluationTemplate, $enrollments),
            'eligibleElectiveSubjectsByTerm' => $eligibleElectiveSubjectsByTerm,
            'electivePlacementIndex' => $electivePlacementIndex,
            'completedEnrollmentBySubject' => $completedEnrollmentBySubject,
            'completedSubjectIds' => $completedSubjectIds,
            'programOfStudy' => $programOfStudy,
            'programOfStudySummary' => [
                'cumulativeGpa' => $this->gpaForEnrollments($enrollments),
            ],
            'dashboard' => $this->dashboard($prospectus, $enrollments, $completedEnrollmentBySubject),
            'evaluationProgress' => $this->evaluationProgress(
                $evaluationTemplate,
                $enrollments,
                $completedEnrollmentBySubject,
            ),
        ];
    }

    public function matchedProspectus(StudentProfile $studentProfile): ?Prospectus
    {
        $studentProfile->loadMissing('activeAdmission.course', 'activeAdmission.major');

        if ($studentProfile->activeAdmission === null) {
            return null;
        }

        $majorId = $studentProfile->activeAdmission->major_id;

        $query = Prospectus::query()
            ->where('course_id', $studentProfile->activeAdmission->course_id)
            ->where(function (Builder $query) use ($majorId): void {
                $query->whereNull('major_id');

                if ($majorId !== null) {
                    $query->orWhere('major_id', $majorId);
                }
            })
            ->orderByRaw('case when major_id is null then 1 else 0 end')
            ->with(['terms.subjects.requisites.requisiteSubject', 'terms.electives']);

        return (clone $query)
            ->where('is_active', true)
            ->first()
            ?? $query->first();
    }

    public function isPassingEnrollment(StudentSubjectEnrollment $enrollment): bool
    {
        $grade = $this->authoritativeGrade($enrollment);

        if ($grade === '') {
            return false;
        }

        if ($enrollment->subject->grading_system === 'numerical') {
            return is_numeric($grade) && (float) $grade <= 3.0;
        }

        return ! in_array(Str::lower($grade), [
            'f',
            'failed',
            '5',
            '5.0',
            'inc',
            'incomplete',
            'ip',
            'drp',
            'dropped',
            'w',
            'withdrawn',
        ], true);
    }

    public function termSortOrder(string $termName): int
    {
        return match ($termName) {
            '1st Semester' => 1,
            '2nd Semester' => 2,
            'Summer Term' => 3,
            default => 99,
        };
    }

    private function authoritativeGrade(StudentSubjectEnrollment $enrollment): string
    {
        if (! $this->isAuthoritativeEnrollment($enrollment)) {
            return '';
        }

        $grade = trim((string) $enrollment->grade);

        if ($this->isIncGrade($grade) && $this->hasValidIncCompletion($enrollment)) {
            return trim((string) $enrollment->completion_grade);
        }

        return $grade;
    }

    private function isAuthoritativeEnrollment(StudentSubjectEnrollment $enrollment): bool
    {
        return $this->normalizedEnrollmentStatus($enrollment) === 'confirmed';
    }

    private function isIncGrade(string $grade): bool
    {
        return in_array(Str::lower(trim($grade)), ['inc', 'incomplete'], true);
    }

    private function hasValidIncCompletion(StudentSubjectEnrollment $enrollment): bool
    {
        $completionGrade = trim((string) $enrollment->completion_grade);
        $completionConfirmedAt = $enrollment->completion_confirmed_at;

        if ($completionGrade === '' || $completionConfirmedAt === null) {
            return false;
        }

        $deadline = $this->incCompletionDeadline($enrollment);

        if ($deadline === null) {
            return true;
        }

        return $completionConfirmedAt->lte($deadline->copy()->endOfDay());
    }

    private function incCompletionDeadline(StudentSubjectEnrollment $enrollment)
    {
        $academicYearStart = $this->academicYearStartFromSchoolYear($enrollment->school_year);

        if ($academicYearStart === null) {
            return null;
        }

        return AcademicTerm::query()
            ->where('academic_year_start', $academicYearStart + 1)
            ->where('term_name', $enrollment->term_name)
            ->value('date_to');
    }

    private function academicYearStartFromSchoolYear(?string $schoolYear): ?int
    {
        if (! is_string($schoolYear) || preg_match('/^(\d{4})-\d{4}$/', $schoolYear, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    private function programOfStudyStatus(?StudentSubjectEnrollment $enrollment): string
    {
        if ($enrollment === null) {
            return 'not_enrolled';
        }

        return $this->normalizedEnrollmentStatus($enrollment);
    }

    private function normalizedEnrollmentStatus(StudentSubjectEnrollment $enrollment): string
    {
        $status = trim((string) $enrollment->getAttribute('status'));

        if ($status !== '') {
            return $status;
        }

        if (filled($enrollment->getAttribute('grade')) || filled($enrollment->getAttribute('confirmed_at'))) {
            return 'confirmed';
        }

        if (filled($enrollment->getAttribute('submitted_grade')) || filled($enrollment->getAttribute('submitted_at'))) {
            return 'grade_submitted';
        }

        return 'in_progress';
    }

    public function calculateGpa(Collection $enrollments): ?float
    {
        return $this->gpaForEnrollments($enrollments);
    }

    public function academicTermGpa(Collection $enrollments, string $schoolYear, string $termName): ?float
    {
        return $this->gpaForEnrollments(
            $enrollments->filter(fn (StudentSubjectEnrollment $enrollment) => $enrollment->school_year === $schoolYear
                && $enrollment->term_name === $termName)
        );
    }

    private function matchedEvaluationTemplate(StudentProfile $studentProfile): ?EvaluationTemplate
    {
        $studentProfile->loadMissing('activeAdmission');

        if ($studentProfile->activeAdmission === null) {
            return null;
        }

        $majorId = $studentProfile->activeAdmission->major_id;

        return EvaluationTemplate::query()
            ->where('course_id', $studentProfile->activeAdmission->course_id)
            ->where(function (Builder $query) use ($majorId): void {
                $query->whereNull('major_id');

                if ($majorId !== null) {
                    $query->orWhere('major_id', $majorId);
                }
            })
            ->orderByRaw('case when major_id is null then 1 else 0 end')
            ->with('classifications.subjects')
            ->first();
    }

    private function enrollmentKey(int $subjectId, int $yearLevel, string $termName): string
    {
        return $subjectId.'|'.$yearLevel.'|'.$termName;
    }

    /**
     * @return array<string, int>
     */
    private function evaluationSummary(?EvaluationTemplate $evaluationTemplate, Collection $enrollments): array
    {
        if ($evaluationTemplate === null) {
            return [
                'totalSubjects' => 0,
                'recordedGrades' => 0,
                'pendingSubjects' => 0,
            ];
        }

        $subjectIds = $evaluationTemplate->classifications
            ->flatMap(fn ($classification) => $classification->subjects->pluck('id'))
            ->unique();

        $recordedGrades = $enrollments
            ->whereIn('subject_id', $subjectIds)
            ->filter(fn (StudentSubjectEnrollment $enrollment) => filled($this->authoritativeGrade($enrollment)))
            ->count();

        return [
            'totalSubjects' => $subjectIds->count(),
            'recordedGrades' => $recordedGrades,
            'pendingSubjects' => max($subjectIds->count() - $recordedGrades, 0),
        ];
    }

    /**
     * @return Collection<int, Subject>
     */
    public function eligibleElectiveSubjects(
        StudentProfile $studentProfile,
        ?Prospectus $prospectus,
        ?Collection $categories = null,
    ): Collection {
        $studentProfile->loadMissing('activeAdmission');

        if ($studentProfile->activeAdmission === null || $prospectus === null) {
            return collect();
        }

        $courseId = $studentProfile->activeAdmission->course_id;
        $majorId = $studentProfile->activeAdmission->major_id;
        $categoryValues = ($categories ?? collect())
            ->map(fn ($category) => $category instanceof ElectiveCategory ? $category->value : (string) $category)
            ->filter()
            ->values();
        $prospectusSubjectIds = $prospectus->terms
            ->flatMap(fn (ProspectusTerm $term) => $term->subjects->pluck('id'))
            ->unique();

        return Subject::query()
            ->whereHas('electiveScopes', function (Builder $query) use ($courseId, $majorId, $categoryValues): void {
                $query->where('course_id', $courseId)
                    ->where(function (Builder $scopeQuery) use ($majorId): void {
                        $scopeQuery->whereNull('major_id');

                        if ($majorId !== null) {
                            $scopeQuery->orWhere('major_id', $majorId);
                        }
                    })
                    ->when($categoryValues->isNotEmpty(), function (Builder $categoryQuery) use ($categoryValues): void {
                        $categoryQuery->whereIn('category', $categoryValues->all());
                    });
            })
            ->when(
                $prospectusSubjectIds->isNotEmpty(),
                fn (Builder $query) => $query->whereNotIn('id', $prospectusSubjectIds)
            )
            ->with('electiveScopes')
            ->orderBy('subject_code')
            ->get();
    }

    /**
     * @return Collection<int, Collection<int, array{number:int,elective:\App\Models\ProspectusTermElective,fulfilledBy:?StudentSubjectEnrollment,prerequisites:string,corequisites:string}>>
     */
    private function electivePlacementIndex(
        Prospectus $prospectus,
        Collection $enrollments,
        int $courseId,
        ?int $majorId,
    ): Collection {
        $prospectusSubjectIds = $prospectus->terms
            ->flatMap(fn (ProspectusTerm $term) => $term->subjects->pluck('id'))
            ->unique();

        $passedElectiveEnrollments = $enrollments
            ->filter(fn (StudentSubjectEnrollment $enrollment) => $this->isPassingEnrollment($enrollment)
                && $this->subjectMatchesElectiveScope($enrollment->subject, $courseId, $majorId))
            ->sortBy(fn (StudentSubjectEnrollment $enrollment) => sprintf(
                '%04d-%02d-%s-%09d',
                $enrollment->year_level,
                $this->termSortOrder($enrollment->term_name),
                $enrollment->subject->subject_code,
                $enrollment->id,
            ))
            ->values();
        $passedFallbackElectiveEnrollments = $enrollments
            ->filter(fn (StudentSubjectEnrollment $enrollment) => $this->isPassingEnrollment($enrollment)
                && ! $prospectusSubjectIds->contains($enrollment->subject_id))
            ->sortBy(fn (StudentSubjectEnrollment $enrollment) => sprintf(
                '%04d-%02d-%s-%09d',
                $enrollment->year_level,
                $this->termSortOrder($enrollment->term_name),
                $enrollment->subject->subject_code,
                $enrollment->id,
            ))
            ->values();

        $electiveNumber = 1;

        return $prospectus->terms->mapWithKeys(function (ProspectusTerm $term) use (
            $passedElectiveEnrollments,
            $passedFallbackElectiveEnrollments,
            $courseId,
            $majorId,
            &$electiveNumber,
        ): array {
            $fallbackEnrollmentsForTerm = $passedFallbackElectiveEnrollments
                ->filter(fn (StudentSubjectEnrollment $enrollment) => $enrollment->year_level === $term->year_level
                    && $enrollment->term_name === $term->term_name)
                ->values();

            return [
                $term->id => $term->electives->map(function ($elective) use (
                    $passedElectiveEnrollments,
                    $fallbackEnrollmentsForTerm,
                    $courseId,
                    $majorId,
                    &$electiveNumber,
                ): array {
                    $fulfilledBy = $passedElectiveEnrollments->first(function (StudentSubjectEnrollment $enrollment) use ($elective): bool {
                        if (($enrollment->used_for_elective_placeholder ?? false) === true) {
                            return false;
                        }

                        return $this->subjectElectiveCategoriesForScope(
                            $enrollment->subject,
                            $enrollment->studentProfile->activeAdmission?->course_id,
                            $enrollment->studentProfile->activeAdmission?->major_id,
                        )->contains($elective->category?->value);
                    });

                    if ($fulfilledBy === null) {
                        $fulfilledBy = $fallbackEnrollmentsForTerm->first(function (StudentSubjectEnrollment $enrollment): bool {
                            return ($enrollment->used_for_elective_placeholder ?? false) !== true;
                        });
                    }

                    if ($fulfilledBy !== null) {
                        $fulfilledBy->used_for_elective_placeholder = true;
                    }

                    $requisites = $fulfilledBy === null
                        ? [
                            'prerequisites' => 'As the course requires',
                            'corequisites' => 'As the course requires',
                        ]
                        : $this->requisiteLabels($fulfilledBy->subject, $courseId, $majorId);

                    return [
                        'number' => $electiveNumber++,
                        'elective' => $elective,
                        'fulfilledBy' => $fulfilledBy,
                        'prerequisites' => $requisites['prerequisites'],
                        'corequisites' => $requisites['corequisites'],
                    ];
                }),
            ];
        });
    }

    /**
     * @return Collection<int, StudentSubjectEnrollment>
     */
    private function completedEnrollmentBySubject(Collection $enrollments): Collection
    {
        return $enrollments
            ->filter(fn (StudentSubjectEnrollment $enrollment) => $this->isPassingEnrollment($enrollment))
            ->groupBy('subject_id')
            ->map(fn (Collection $subjectEnrollments) => $subjectEnrollments->sortBy(fn (StudentSubjectEnrollment $enrollment) => sprintf(
                '%s-%04d-%02d-%09d',
                $enrollment->school_year,
                $enrollment->year_level,
                $this->termSortOrder($enrollment->term_name),
                $enrollment->id,
            ))->first())
            ->values()
            ->keyBy('subject_id');
    }

    /**
     * @return array<string, mixed>
     */
    private function dashboard(?Prospectus $prospectus, Collection $enrollments, Collection $completedEnrollmentBySubject): array
    {
        $prospectusUnits = $prospectus === null
            ? 0.0
            : (float) $prospectus->terms->sum(function (ProspectusTerm $term): float {
                $subjectUnits = (float) $term->subjects->sum(fn (Subject $subject) => (float) $subject->credit_units);
                $electiveUnits = (float) $term->electives->sum('units');

                return $subjectUnits + $electiveUnits;
            });

        $earnedUnits = (float) $completedEnrollmentBySubject
            ->sum(fn (StudentSubjectEnrollment $enrollment) => (float) $enrollment->subject->credit_units);

        $gradeDistribution = $enrollments
            ->filter(fn (StudentSubjectEnrollment $enrollment) => filled($this->authoritativeGrade($enrollment)))
            ->groupBy(fn (StudentSubjectEnrollment $enrollment) => $this->authoritativeGrade($enrollment))
            ->map(fn (Collection $group) => $group->count())
            ->sortKeys(SORT_NATURAL)
            ->map(fn (int $count, string $grade) => [
                'grade' => $grade,
                'count' => $count,
            ])
            ->values();

        return [
            'earnedUnits' => $earnedUnits,
            'unearnedUnits' => max($prospectusUnits - $earnedUnits, 0),
            'prospectusUnits' => $prospectusUnits,
            'gradeDistribution' => $gradeDistribution,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function programOfStudy(
        StudentProfile $studentProfile,
        ?Prospectus $prospectus,
        Collection $latestEnrollmentBySubject,
        Collection $attemptsBySubject,
        Collection $electivePlacementIndex,
        Collection $enrollments,
    ): Collection {
        if ($prospectus === null || $enrollments->isEmpty()) {
            return collect();
        }

        $studentProfile->loadMissing('activeAdmission');

        $courseId = $studentProfile->activeAdmission?->course_id;
        $majorId = $studentProfile->activeAdmission?->major_id;
        $electivePlacementsByEnrollment = $electivePlacementIndex
            ->flatMap(fn (Collection $placements) => $placements)
            ->filter(fn (array $placement): bool => $placement['fulfilledBy'] !== null)
            ->mapWithKeys(fn (array $placement): array => [
                (int) $placement['fulfilledBy']->id => $placement,
            ]);

        return $enrollments
            ->groupBy('school_year')
            ->map(function (Collection $schoolYearEnrollments, string $schoolYear) use ($attemptsBySubject, $courseId, $electivePlacementsByEnrollment, $majorId): array {
                $terms = $schoolYearEnrollments
                    ->groupBy(fn (StudentSubjectEnrollment $enrollment): string => $enrollment->year_level.'|'.$enrollment->term_name)
                    ->map(function (Collection $termEnrollments) use ($attemptsBySubject, $courseId, $electivePlacementsByEnrollment, $majorId): array {
                        /** @var StudentSubjectEnrollment $firstEnrollment */
                        $firstEnrollment = $termEnrollments->first();

                        return [
                            'schoolYear' => (string) $firstEnrollment->school_year,
                            'yearLevel' => (int) $firstEnrollment->year_level,
                            'term' => (object) [
                                'term_name' => (string) $firstEnrollment->term_name,
                            ],
                            'semesterGpa' => $this->gpaForEnrollments($termEnrollments),
                            'rows' => $termEnrollments
                                ->map(function (StudentSubjectEnrollment $enrollment) use ($attemptsBySubject, $courseId, $electivePlacementsByEnrollment, $majorId): array {
                                    $placement = $electivePlacementsByEnrollment->get((int) $enrollment->id);
                                    $attempts = $attemptsBySubject->get($enrollment->subject_id, collect());
                                    $attemptIndex = $attempts->search(fn (StudentSubjectEnrollment $attempt): bool => (int) $attempt->id === (int) $enrollment->id);
                                    $priorAttempts = $attemptIndex === false
                                        ? collect()
                                        : $attempts->take((int) $attemptIndex);
                                    $requisites = $placement === null
                                        ? $this->requisiteLabels($enrollment->subject, $courseId, $majorId)
                                        : [
                                            'prerequisites' => $placement['prerequisites'],
                                            'corequisites' => $placement['corequisites'],
                                        ];

                                    return [
                                        'type' => $placement === null ? 'subject' : 'elective',
                                        'subject' => $enrollment->subject,
                                        'enrollment' => $enrollment,
                                        'attemptNumber' => $enrollment->attempt_number,
                                        'code' => $enrollment->subject->subject_code,
                                        'title' => $enrollment->subject->subject_title,
                                        'units' => (float) $enrollment->subject->credit_units,
                                        'prerequisites' => $requisites['prerequisites'],
                                        'corequisites' => $requisites['corequisites'],
                                        'grade' => $this->programOfStudyDisplayedGrade($enrollment),
                                        'rawGrade' => trim((string) $enrollment->grade),
                                        'gradeContext' => $this->programOfStudyGradeContext($enrollment),
                                        'attemptHistory' => $this->retakeHistory($priorAttempts),
                                        'isRetaken' => ((int) $enrollment->attempt_number) > 1,
                                        'submittedGrade' => $enrollment->submitted_grade,
                                        'status' => $this->programOfStudyStatus($enrollment),
                                        'completed' => $this->isPassingEnrollment($enrollment),
                                        'failed' => $this->programOfStudyRowShouldBeFilledRed($enrollment),
                                        'fulfilledBy' => $placement === null ? null : $enrollment,
                                        'electiveLabel' => $placement['elective']->name ?? null,
                                    ];
                                })
                                ->values(),
                        ];
                    })
                    ->sortBy(fn (array $termData): array => [$termData['yearLevel'], $this->termSortOrder($termData['term']->term_name)])
                    ->values();

                return [
                    'schoolYear' => $schoolYear,
                    'yearLevel' => (int) ($terms->first()['yearLevel'] ?? 0),
                    'terms' => $terms,
                ];
            })
            ->sortBy('schoolYear')
            ->values();
    }

    private function programOfStudyGradeContext(StudentSubjectEnrollment $enrollment): ?string
    {
        $grade = trim((string) $enrollment->grade);

        if (! $this->isIncGrade($grade)) {
            return null;
        }

        return $this->hasValidIncCompletion($enrollment)
            ? 'Completed from INC'
            : 'INC lapsed';
    }

    private function programOfStudyDisplayedGrade(StudentSubjectEnrollment $enrollment): string
    {
        $grade = $this->authoritativeGrade($enrollment);

        if ($grade !== '') {
            return $grade;
        }

        $rawGrade = trim((string) $enrollment->grade);

        return $this->normalizedEnrollmentStatus($enrollment) === 'confirmed'
            ? $rawGrade
            : '';
    }

    private function programOfStudyRowShouldBeFilledRed(StudentSubjectEnrollment $enrollment): bool
    {
        if (! $this->isAuthoritativeEnrollment($enrollment)) {
            return false;
        }

        if ($this->isLapsedIncEnrollment($enrollment)) {
            return true;
        }

        $grade = $this->authoritativeGrade($enrollment);

        if ($grade === '') {
            return false;
        }

        if (is_numeric($grade)) {
            return round((float) $grade, 5) >= 5.0;
        }

        return Str::upper($grade) === 'F';
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function evaluationProgress(
        ?EvaluationTemplate $evaluationTemplate,
        Collection $enrollments,
        Collection $completedEnrollmentBySubject,
    ): Collection {
        if ($evaluationTemplate === null) {
            return collect();
        }

        $recordedEnrollmentsBySubject = $enrollments
            ->filter(fn (StudentSubjectEnrollment $enrollment) => filled($this->authoritativeGrade($enrollment)))
            ->groupBy('subject_id')
            ->map(fn (Collection $subjectEnrollments) => $subjectEnrollments->sortBy(fn (StudentSubjectEnrollment $enrollment) => sprintf(
                '%s-%04d-%02d-%09d',
                $enrollment->school_year,
                $enrollment->year_level,
                $this->termSortOrder($enrollment->term_name),
                $enrollment->id,
            ))->last());

        return $evaluationTemplate->classifications->map(function ($classification) use (
            $recordedEnrollmentsBySubject,
            $completedEnrollmentBySubject,
        ): array {
            $subjects = $classification->subjects;
            $isNstp = $this->isNstpClassification($classification->name, $subjects);
            $recordedCount = $subjects->filter(fn (Subject $subject) => $recordedEnrollmentsBySubject->has($subject->id))->count();
            $completedCount = $subjects->filter(fn (Subject $subject) => $completedEnrollmentBySubject->has($subject->id))->count();
            $requiredUnits = (float) $subjects->sum(fn (Subject $subject) => (float) $subject->credit_units);
            $earnedUnits = (float) $subjects
                ->filter(fn (Subject $subject) => $completedEnrollmentBySubject->has($subject->id))
                ->sum(fn (Subject $subject) => (float) $subject->credit_units);

            return [
                'classification' => $classification,
                'subjects' => $subjects,
                'isNstp' => $isNstp,
                'summarySortOrder' => $isNstp ? 1 : 0,
                'subjectRows' => $subjects->map(function (Subject $subject) use ($recordedEnrollmentsBySubject, $completedEnrollmentBySubject): array {
                    $recordedEnrollment = $recordedEnrollmentsBySubject->get($subject->id);
                    $completedEnrollment = $completedEnrollmentBySubject->get($subject->id);

                    return [
                        'subject' => $subject,
                        'grade' => $recordedEnrollment !== null ? $this->authoritativeGrade($recordedEnrollment) : '',
                        'recordedEnrollment' => $recordedEnrollment,
                        'completedEnrollment' => $completedEnrollment,
                        'completed' => $completedEnrollment !== null,
                    ];
                }),
                'totalSubjects' => $subjects->count(),
                'recordedCount' => $recordedCount,
                'completedCount' => $completedCount,
                'requiredUnits' => $requiredUnits,
                'earnedUnits' => $earnedUnits,
            ];
        });
    }

    private function isNstpClassification(string $classificationName, Collection $subjects): bool
    {
        if (Str::contains(Str::upper($classificationName), 'NSTP')) {
            return true;
        }

        return $subjects->isNotEmpty()
            && $subjects->every(fn (Subject $subject) => ! $subject->counts_toward_gpa)
            && $subjects->contains(function (Subject $subject): bool {
                $subjectLabel = Str::upper(trim($subject->subject_code.' '.$subject->subject_title));

                return Str::contains($subjectLabel, 'NSTP')
                    || Str::contains($subjectLabel, 'NATIONAL SERVICE TRAINING PROGRAM')
                    || Str::contains($subjectLabel, 'ROTC')
                    || Str::contains($subjectLabel, 'CWTS')
                    || Str::contains($subjectLabel, 'LTS');
            });
    }

    /**
     * @return Collection<int, string>
     */
    private function subjectElectiveCategoriesForScope(?Subject $subject, ?int $courseId, ?int $majorId): Collection
    {
        if ($subject === null || $courseId === null) {
            return collect();
        }

        return $subject->electiveScopes
            ->filter(function ($scope) use ($courseId, $majorId): bool {
                if ($scope->course_id !== $courseId) {
                    return false;
                }

                return $scope->major_id === null || $scope->major_id === $majorId;
            })
            ->map(fn ($scope) => $scope->category?->value)
            ->filter()
            ->values();
    }

    private function subjectMatchesElectiveScope(?Subject $subject, int $courseId, ?int $majorId): bool
    {
        return $this->subjectElectiveCategoriesForScope($subject, $courseId, $majorId)->isNotEmpty();
    }

    /**
     * @return array{prerequisites:string,corequisites:string}
     */
    private function requisiteLabels(?Subject $subject, ?int $courseId, ?int $majorId): array
    {
        if ($subject === null) {
            return [
                'prerequisites' => '—',
                'corequisites' => '—',
            ];
        }

        $prerequisites = $subject->applicableRequisites($courseId, $majorId, 'prerequisite')
            ->map(fn ($requisite) => $requisite->requisiteSubject?->subject_code)
            ->filter()
            ->implode(', ');
        $corequisites = $subject->applicableRequisites($courseId, $majorId, 'corequisite')
            ->map(fn ($requisite) => $requisite->requisiteSubject?->subject_code)
            ->filter()
            ->implode(', ');

        return [
            'prerequisites' => $prerequisites !== '' ? $prerequisites : '—',
            'corequisites' => $corequisites !== '' ? $corequisites : '—',
        ];
    }

    private function gpaForEnrollments(Collection $enrollments): ?float
    {
        $gpaBearingEnrollments = $enrollments
            ->map(function (StudentSubjectEnrollment $enrollment): ?array {
                $gradePoint = $this->gradePointForEnrollment($enrollment);
                $units = (float) $enrollment->subject->credit_units;

                if ($gradePoint === null || $units <= 0) {
                    return null;
                }

                return [
                    'gradePoint' => $gradePoint,
                    'units' => $units,
                ];
            })
            ->filter();

        $totalUnits = (float) $gpaBearingEnrollments->sum('units');

        if ($totalUnits <= 0) {
            return null;
        }

        $weightedGradePoints = (float) $gpaBearingEnrollments->sum(
            fn (array $entry): float => $entry['gradePoint'] * $entry['units']
        );

        return round($weightedGradePoints / $totalUnits, 5);
    }

    private function gradePointForEnrollment(StudentSubjectEnrollment $enrollment): ?float
    {
        $grade = $this->authoritativeGrade($enrollment);

        if ($grade === '' || ! $enrollment->subject->counts_toward_gpa) {
            return null;
        }

        if ($this->isLapsedIncEnrollment($enrollment) || $this->isDroppedGrade($grade)) {
            return 5.00;
        }

        if (is_numeric($grade)) {
            return round((float) $grade, 5);
        }

        return match (Str::upper($grade)) {
            'A+', 'A' => 1.00,
            'A-' => 1.25,
            'B+' => 1.50,
            'B' => 1.75,
            'B-' => 2.00,
            'C+' => 2.25,
            'C' => 2.50,
            'C-' => 2.75,
            'D+', 'D' => 3.00,
            'F' => 5.00,
            default => null,
        };
    }

    private function isLapsedIncEnrollment(StudentSubjectEnrollment $enrollment): bool
    {
        if (! $this->isAuthoritativeEnrollment($enrollment)) {
            return false;
        }

        return $this->isIncGrade((string) $enrollment->grade)
            && ! $this->hasValidIncCompletion($enrollment);
    }

    private function isDroppedGrade(string $grade): bool
    {
        return in_array(Str::lower(trim($grade)), ['drp', 'dropped'], true);
    }

    private function schoolYearForTerm(ProspectusTerm $term, Collection $enrollments, ?int $startingSchoolYear): string
    {
        $schoolYear = $enrollments
            ->filter(fn (StudentSubjectEnrollment $enrollment) => $enrollment->year_level === $term->year_level && $enrollment->term_name === $term->term_name)
            ->pluck('school_year')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first();

        if (is_string($schoolYear) && $schoolYear !== '') {
            return $schoolYear;
        }

        if ($startingSchoolYear === null) {
            return 'TBA';
        }

        $year = $startingSchoolYear + ($term->year_level - 1);

        return $year.'-'.($year + 1);
    }

    private function startingSchoolYear(StudentProfile $studentProfile, Collection $enrollments): ?int
    {
        $schoolYear = $enrollments
            ->pluck('school_year')
            ->filter()
            ->sort()
            ->first();

        if (is_string($schoolYear) && preg_match('/^(\d{4})-\d{4}$/', $schoolYear, $matches) === 1) {
            return (int) $matches[1];
        }

        return $studentProfile->activeAdmission?->admission_date?->year;
    }

    /**
     * @param  Collection<int, StudentSubjectEnrollment>  $attempts
     * @return array<int, array{attemptNumber:int,schoolYear:string,termName:string,result:string,status:string}>
     */
    private function retakeHistory(Collection $attempts): array
    {
        if ($attempts->count() <= 1) {
            return [];
        }

        return $attempts
            ->map(fn (StudentSubjectEnrollment $enrollment): array => [
                'attemptNumber' => (int) $enrollment->attempt_number,
                'schoolYear' => (string) $enrollment->school_year,
                'termName' => (string) $enrollment->term_name,
                'result' => $this->attemptResultLabel($enrollment),
                'status' => $this->programOfStudyStatus($enrollment),
            ])
            ->all();
    }

    private function attemptResultLabel(StudentSubjectEnrollment $enrollment): string
    {
        $grade = $this->programOfStudyDisplayedGrade($enrollment);

        if ($grade !== '') {
            return $grade;
        }

        return match ($this->programOfStudyStatus($enrollment)) {
            'grade_submitted' => 'Grade Submitted',
            'in_progress' => 'In Progress',
            default => 'Not Recorded',
        };
    }
}