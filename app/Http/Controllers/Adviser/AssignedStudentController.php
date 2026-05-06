<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\Prospectus;
use App\Models\ProspectusTerm;
use App\Models\StudentProfile;
use App\Models\StudentSubjectEnrollment;
use App\Models\Subject;
use App\Models\User;
use App\Support\GraduationEligibility;
use App\Support\StudentAcademicView;
use App\Support\SubjectProgressionGraph;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssignedStudentController extends Controller
{
    public function __construct(
        public StudentAcademicView $studentAcademicView,
        public GraduationEligibility $graduationEligibility,
        public SubjectProgressionGraph $subjectProgressionGraph,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $adviser */
        $adviser = $request->user();

        $activeAcademicTerm = AcademicTerm::active();
        [$previousSchoolYear, $previousTermName] = $this->previousSemesterContext($activeAcademicTerm);

        $students = $this->assignedStudentsQuery($adviser)
            ->with(['user', 'subjectEnrollments.subject'])
            ->get()
            ->map(function (StudentProfile $studentProfile) use ($previousSchoolYear, $previousTermName): array {
                return [
                    'studentProfile' => $studentProfile,
                    'displayName' => $studentProfile->user->adviserDisplayName(),
                    'previousSemesterGpa' => $previousSchoolYear !== null && $previousTermName !== null
                        ? $this->studentAcademicView->academicTermGpa($studentProfile->subjectEnrollments, $previousSchoolYear, $previousTermName)
                        : null,
                    'cumulativeGpa' => $this->studentAcademicView->calculateGpa($studentProfile->subjectEnrollments),
                ];
            });

        $sort = $request->string('sort')->value() ?: 'name';
        $direction = $request->string('direction')->value() === 'asc' ? 'asc' : 'desc';

        $students = $this->sortAdvisees($students, $sort, $direction)->values();

        return view('adviser.students.index', [
            'activeAcademicTerm' => $activeAcademicTerm,
            'direction' => $direction,
            'sort' => $sort,
            'students' => $students,
        ]);
    }

    public function show(Request $request, StudentProfile $studentProfile): View
    {
        /** @var User $adviser */
        $adviser = $request->user();

        abort_unless(
            $this->assignedStudentsQuery($adviser)->whereKey($studentProfile->id)->exists(),
            404
        );

        $studentProfile->loadMissing('activeAdmission.course', 'activeAdmission.major');
        $academicView = $this->studentAcademicView->build($studentProfile);
        $activeAdmission = $studentProfile->activeAdmission;
        $graph = $activeAdmission?->course === null
            ? $this->emptyGraph()
            : $this->subjectProgressionGraph->build($activeAdmission->course, $activeAdmission->major, [
                'gradeLabels' => $this->gradeLabels($academicView['enrollments']),
            ]);
        $graduationEligibility = $this->graduationEligibility->evaluate($studentProfile, $academicView);
        $currentTermEnrollment = $this->currentTermEnrollmentData(
            $studentProfile,
            $academicView['prospectus'],
            $academicView['enrollments'],
        );

        return view('adviser.students.show', [
            'studentProfile' => $studentProfile,
            'currentTermEnrollment' => $currentTermEnrollment,
            'selectedCourse' => $activeAdmission?->course,
            'selectedMajor' => $activeAdmission?->major,
            'graduationEligibility' => $graduationEligibility,
            'tab' => in_array($request->query('tab'), ['program-of-study', 'evaluation', 'subject-progression'], true)
                ? $request->query('tab')
                : 'program-of-study',
            ...$academicView,
            ...$graph,
        ]);
    }

    public function markGraduating(Request $request, StudentProfile $studentProfile): RedirectResponse
    {
        /** @var User $adviser */
        $adviser = $request->user();

        abort_unless(
            $this->assignedStudentsQuery($adviser)->whereKey($studentProfile->id)->exists(),
            404
        );

        $tab = in_array($request->string('tab')->value(), ['program-of-study', 'evaluation', 'subject-progression'], true)
            ? $request->string('tab')->value()
            : 'program-of-study';

        if ($studentProfile->is_graduating) {
            return redirect()
                ->route('adviser.students.show', ['studentProfile' => $studentProfile, 'tab' => $tab])
                ->with('status', 'Student is already marked as graduating.');
        }

        $graduationEligibility = $this->graduationEligibility->evaluate($studentProfile);

        if (! $graduationEligibility['eligible']) {
            throw ValidationException::withMessages([
                'graduation' => 'This student does not yet meet the graduating requirements.',
            ]);
        }

        $studentProfile->forceFill([
            'is_graduating' => true,
        ])->save();

        return redirect()
            ->route('adviser.students.show', ['studentProfile' => $studentProfile, 'tab' => $tab])
            ->with('status', 'Student marked as graduating.');
    }

    public function storeCurrentTermEnrollment(Request $request, StudentProfile $studentProfile): RedirectResponse
    {
        /** @var User $adviser */
        $adviser = $request->user();

        abort_unless(
            $this->assignedStudentsQuery($adviser)->whereKey($studentProfile->id)->exists(),
            404
        );

        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
        ]);

        $academicTerm = AcademicTerm::active();

        if ($academicTerm === null) {
            throw ValidationException::withMessages([
                'current_term_subject_id' => 'No active academic term is available right now.',
            ]);
        }

        $prospectus = $this->studentAcademicView->matchedProspectus($studentProfile);

        if ($prospectus === null) {
            throw ValidationException::withMessages([
                'current_term_subject_id' => 'No active prospectus matches this student yet.',
            ]);
        }

        $currentTermEnrollment = $this->currentTermEnrollmentData(
            $studentProfile,
            $prospectus,
            $studentProfile->subjectEnrollments()->with('subject')->get(),
            $academicTerm,
        );

        $selectedSubjectId = (int) $validated['subject_id'];
        $allowedSubjectIds = $currentTermEnrollment['subjects']->pluck('id');

        if (! $allowedSubjectIds->contains($selectedSubjectId)) {
            throw ValidationException::withMessages([
                'current_term_subject_id' => 'The selected subject is not available for the current academic term.',
            ]);
        }

        $schoolYear = $academicTerm->academic_year_start.'-'.$academicTerm->academicYearEnd();
        $enrollment = StudentSubjectEnrollment::query()
            ->where('student_profile_id', $studentProfile->id)
            ->where('subject_id', $selectedSubjectId)
            ->where(function (Builder $query) use ($academicTerm, $schoolYear, $studentProfile): void {
                $query->where('academic_term_id', $academicTerm->id)
                    ->orWhere(function (Builder $fallbackQuery) use ($schoolYear, $academicTerm, $studentProfile): void {
                        $fallbackQuery
                            ->whereNull('academic_term_id')
                            ->where('school_year', $schoolYear)
                            ->where('year_level', $studentProfile->year_level)
                            ->where('term_name', $academicTerm->term_name);
                    });
            })
            ->firstOrNew();

        if (! $enrollment->exists) {
            $enrollment->attempt_number = $this->nextAttemptNumber($studentProfile, $selectedSubjectId);
        }

        $enrollment->fill([
            'student_profile_id' => $studentProfile->id,
            'subject_id' => $selectedSubjectId,
            'academic_term_id' => $academicTerm->id,
            'school_year' => $schoolYear,
            'year_level' => $studentProfile->year_level,
            'term_name' => $academicTerm->term_name,
            'status' => 'in_progress',
            'grade' => null,
            'submitted_grade' => null,
            'submitted_at' => null,
            'confirmed_at' => null,
            'completion_grade' => null,
            'completion_submitted_at' => null,
            'completion_confirmed_at' => null,
            'status_resolved_at' => null,
            'recorded_by_user_id' => $adviser->id,
        ]);
        $enrollment->save();

        return redirect()
            ->route('adviser.students.show', ['studentProfile' => $studentProfile, 'tab' => 'program-of-study'])
            ->with('status', 'Current-term enrollment saved successfully.');
    }

    public function submitCurrentTermGrade(
        Request $request,
        StudentProfile $studentProfile,
        StudentSubjectEnrollment $studentSubjectEnrollment,
    ): RedirectResponse {
        /** @var User $adviser */
        $adviser = $request->user();

        $enrollment = $this->managedCurrentTermEnrollment($adviser, $studentProfile, $studentSubjectEnrollment);

        $validated = $request->validate([
            'submitted_grade' => ['required', 'string', 'max:10'],
        ]);

        if ($this->normalizedEnrollmentStatus($enrollment) === 'confirmed') {
            throw ValidationException::withMessages([
                'grade_workflow' => 'This enrollment is already confirmed.',
            ]);
        }

        $enrollment->fill([
            'status' => 'grade_submitted',
            'submitted_grade' => trim((string) $validated['submitted_grade']),
            'submitted_at' => now(),
            'recorded_by_user_id' => $adviser->id,
        ]);
        $enrollment->save();

        return redirect()
            ->route('adviser.students.show', ['studentProfile' => $studentProfile, 'tab' => 'program-of-study'])
            ->with('status', 'Submitted grade saved and waiting for confirmation.');
    }

    public function confirmCurrentTermGrade(
        Request $request,
        StudentProfile $studentProfile,
        StudentSubjectEnrollment $studentSubjectEnrollment,
    ): RedirectResponse {
        /** @var User $adviser */
        $adviser = $request->user();

        $enrollment = $this->managedCurrentTermEnrollment($adviser, $studentProfile, $studentSubjectEnrollment);

        if ($this->normalizedEnrollmentStatus($enrollment) !== 'grade_submitted' || blank($enrollment->submitted_grade)) {
            throw ValidationException::withMessages([
                'grade_workflow' => 'This enrollment does not have a submitted grade to confirm.',
            ]);
        }

        $enrollment->fill([
            'status' => 'confirmed',
            'grade' => trim((string) $enrollment->submitted_grade),
            'confirmed_at' => now(),
            'status_resolved_at' => now(),
            'recorded_by_user_id' => $adviser->id,
        ]);
        $enrollment->save();

        return redirect()
            ->route('adviser.students.show', ['studentProfile' => $studentProfile, 'tab' => 'program-of-study'])
            ->with('status', 'Submitted grade confirmed successfully.');
    }

    public function storeEnrollment(Request $request, StudentProfile $studentProfile): RedirectResponse
    {
        /** @var User $adviser */
        $adviser = $request->user();

        abort_unless(
            $this->assignedStudentsQuery($adviser)->whereKey($studentProfile->id)->exists(),
            404
        );

        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'school_year' => ['required', 'string', 'max:20'],
            'year_level' => ['required', 'integer', 'min:1', 'max:4'],
            'term_name' => ['required', 'string', 'in:1st Semester,2nd Semester,Summer Term'],
            'grade' => ['required', 'string', 'max:10'],
        ]);

        $prospectus = $this->studentAcademicView->matchedProspectus($studentProfile);

        abort_unless($prospectus !== null, 422);

        $term = $prospectus->terms->first(fn (ProspectusTerm $term) => $term->year_level === (int) $validated['year_level']
            && $term->term_name === $validated['term_name']);

        abort_unless($term !== null, 422);

        $allowedSubjectIds = $term->subjects->pluck('id')->unique();
        $allowedElectiveSubjectIds = $term->electives->isEmpty()
            ? collect()
            : $this->studentAcademicView->eligibleElectiveSubjects(
                $studentProfile,
                $prospectus,
                $term->electives->pluck('category')->filter()->values(),
            )->pluck('id');

        abort_unless(
            $allowedSubjectIds->contains((int) $validated['subject_id'])
            || $allowedElectiveSubjectIds->contains((int) $validated['subject_id']),
            422
        );

        StudentSubjectEnrollment::query()->updateOrCreate(
            [
                'student_profile_id' => $studentProfile->id,
                'subject_id' => $validated['subject_id'],
                'year_level' => $validated['year_level'],
                'term_name' => $validated['term_name'],
            ],
            [
                'school_year' => $validated['school_year'],
                'grade' => $validated['grade'],
                'recorded_by_user_id' => $adviser->id,
            ],
        );

        return redirect()
            ->route('adviser.students.show', $studentProfile)
            ->with('status', 'Academic record saved successfully.');
    }

    private function assignedStudentsQuery(User $adviser): Builder
    {
        return StudentProfile::query()
            ->whereHas('adviserBinding.adviserAssignment.adviserProfile', function (Builder $query) use ($adviser): void {
                $query->where('user_id', $adviser->id);
            });
    }

    /**
     * @return array{activeAcademicTerm:?AcademicTerm,subjects:Collection<int, Subject>,searchSubjects:Collection<int, Subject>,schoolYear:?string}
     */
    private function currentTermEnrollmentData(
        StudentProfile $studentProfile,
        ?Prospectus $prospectus,
        Collection $enrollments,
        ?AcademicTerm $academicTerm = null,
    ): array {
        $academicTerm ??= AcademicTerm::active();

        if ($academicTerm === null || $prospectus === null) {
            return [
                'activeAcademicTerm' => $academicTerm,
                'subjects' => collect(),
                'searchSubjects' => collect(),
                'schoolYear' => $academicTerm === null ? null : $academicTerm->academic_year_start.'-'.$academicTerm->academicYearEnd(),
            ];
        }

        $term = $prospectus->terms->first(fn (ProspectusTerm $term) => $term->year_level === (int) $studentProfile->year_level
            && $term->term_name === $academicTerm->term_name);

        if ($term === null) {
            return [
                'activeAcademicTerm' => $academicTerm,
                'subjects' => collect(),
                'searchSubjects' => collect(),
                'schoolYear' => $academicTerm->academic_year_start.'-'.$academicTerm->academicYearEnd(),
            ];
        }

        $currentSchoolYear = $academicTerm->academic_year_start.'-'.$academicTerm->academicYearEnd();
        $alreadyEnrolledSubjectIds = $enrollments
            ->filter(fn (StudentSubjectEnrollment $enrollment) => $enrollment->school_year === $currentSchoolYear
                && (int) $enrollment->year_level === (int) $studentProfile->year_level
                && $enrollment->term_name === $academicTerm->term_name)
            ->pluck('subject_id')
            ->map(fn (int|string $subjectId) => (int) $subjectId)
            ->unique()
            ->values();

        $availableSubjects = $term->subjects
            ->reject(fn (Subject $subject) => $alreadyEnrolledSubjectIds->contains((int) $subject->id))
            ->values();
        $electiveSubjects = $term->electives->isEmpty()
            ? collect()
            : $this->studentAcademicView->eligibleElectiveSubjects(
                $studentProfile,
                $prospectus,
                $term->electives->pluck('category')->filter()->values(),
            )->reject(fn (Subject $subject) => $alreadyEnrolledSubjectIds->contains((int) $subject->id));

        return [
            'activeAcademicTerm' => $academicTerm,
            'subjects' => $availableSubjects
                ->concat($electiveSubjects)
                ->unique('id')
                ->sortBy('subject_code')
                ->values(),
            'searchSubjects' => $electiveSubjects
                ->unique('id')
                ->sortBy('subject_code')
                ->values(),
            'schoolYear' => $academicTerm->academic_year_start.'-'.$academicTerm->academicYearEnd(),
        ];
    }

    /**
     * @return array{0:?string,1:?string}
     */
    private function previousSemesterContext(?AcademicTerm $activeAcademicTerm): array
    {
        if ($activeAcademicTerm === null) {
            return [null, null];
        }

        return match ($activeAcademicTerm->term_name) {
            '2nd Semester' => [
                $activeAcademicTerm->academic_year_start.'-'.$activeAcademicTerm->academicYearEnd(),
                '1st Semester',
            ],
            'Summer Term' => [
                $activeAcademicTerm->academic_year_start.'-'.$activeAcademicTerm->academicYearEnd(),
                '2nd Semester',
            ],
            default => [
                ($activeAcademicTerm->academic_year_start - 1).'-'.$activeAcademicTerm->academic_year_start,
                '2nd Semester',
            ],
        };
    }

    /**
     * @param  Collection<int, array{studentProfile:StudentProfile,displayName:string,previousSemesterGpa:?float,cumulativeGpa:?float}>  $students
     * @return Collection<int, array{studentProfile:StudentProfile,displayName:string,previousSemesterGpa:?float,cumulativeGpa:?float}>
     */
    private function sortAdvisees(Collection $students, string $sort, string $direction): Collection
    {
        $descending = $direction === 'desc';

        return match ($sort) {
            'gpa' => $descending
                ? $students->sortByDesc(fn (array $student): array => [$student['previousSemesterGpa'] !== null ? 1 : 0, $student['previousSemesterGpa'] ?? -1])
                : $students->sortBy(fn (array $student): array => [$student['previousSemesterGpa'] === null ? 1 : 0, $student['previousSemesterGpa'] ?? PHP_FLOAT_MAX]),
            'cgpa' => $descending
                ? $students->sortByDesc(fn (array $student): array => [$student['cumulativeGpa'] !== null ? 1 : 0, $student['cumulativeGpa'] ?? -1])
                : $students->sortBy(fn (array $student): array => [$student['cumulativeGpa'] === null ? 1 : 0, $student['cumulativeGpa'] ?? PHP_FLOAT_MAX]),
            default => $descending
                ? $students->sortByDesc(fn (array $student): string => $student['displayName'])
                : $students->sortBy(fn (array $student): string => $student['displayName']),
        };
    }

    /**
     * @return array<int, string>
     */
    private function gradeLabels(Collection $enrollments): array
    {
        return $enrollments
            ->groupBy('subject_id')
            ->mapWithKeys(fn (Collection $subjectEnrollments, int|string $subjectId) => [
                (int) $subjectId => trim((string) optional($subjectEnrollments->last())->grade),
            ])
            ->all();
    }

    private function nextAttemptNumber(StudentProfile $studentProfile, int $subjectId): int
    {
        return StudentSubjectEnrollment::query()
            ->where('student_profile_id', $studentProfile->id)
            ->where('subject_id', $subjectId)
            ->max('attempt_number') + 1;
    }

    private function managedCurrentTermEnrollment(
        User $adviser,
        StudentProfile $studentProfile,
        StudentSubjectEnrollment $studentSubjectEnrollment,
    ): StudentSubjectEnrollment {
        abort_unless(
            $this->assignedStudentsQuery($adviser)->whereKey($studentProfile->id)->exists(),
            404
        );

        abort_unless(
            (int) $studentSubjectEnrollment->student_profile_id === (int) $studentProfile->id
                && $studentSubjectEnrollment->academic_term_id !== null,
            404
        );

        return $studentSubjectEnrollment;
    }

    private function normalizedEnrollmentStatus(StudentSubjectEnrollment $enrollment): string
    {
        $status = trim((string) $enrollment->status);

        if ($status !== '') {
            return $status;
        }

        if (filled($enrollment->grade) || filled($enrollment->confirmed_at)) {
            return 'confirmed';
        }

        if (filled($enrollment->submitted_grade) || filled($enrollment->submitted_at)) {
            return 'grade_submitted';
        }

        return 'in_progress';
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyGraph(): array
    {
        return [
            'canvasHeight' => 0,
            'canvasWidth' => 0,
            'components' => collect(),
            'componentCount' => 0,
            'edgeCount' => 0,
            'goal' => null,
            'graphEdges' => [],
            'graphNodes' => [],
            'nodeCount' => 0,
        ];
    }
}
