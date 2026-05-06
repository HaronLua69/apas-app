<?php

namespace App\Support;

use App\Models\AcademicTerm;
use App\Models\Prospectus;
use App\Models\ProspectusTerm;
use App\Models\StudentProfile;
use App\Models\StudentSubjectEnrollment;
use App\Models\Subject;
use Illuminate\Support\Collection;

class GraduationEligibility
{
    public function __construct(
        public StudentAcademicView $studentAcademicView,
    ) {}

    /**
     * @param  array<string, mixed>|null  $academicView
     * @return array<string, mixed>
     */
    public function evaluate(StudentProfile $studentProfile, ?array $academicView = null): array
    {
        $academicView ??= $this->studentAcademicView->build($studentProfile);

        /** @var Prospectus|null $prospectus */
        $prospectus = $academicView['prospectus'];
        /** @var Collection<int, StudentSubjectEnrollment> $completedEnrollmentBySubject */
        $completedEnrollmentBySubject = $academicView['completedEnrollmentBySubject'];
        /** @var Collection<int, StudentSubjectEnrollment> $enrollments */
        $enrollments = $academicView['enrollments'];

        $graduationSubjects = $this->graduationSubjects($prospectus);
        /** @var Subject|null $subject197 */
        $subject197 = $graduationSubjects->get('197');
        /** @var Subject|null $subject198 */
        $subject198 = $graduationSubjects->get('198');

        $subject197Passed = $this->subjectPassed($subject197, $completedEnrollmentBySubject);
        $subject197Undergoing = ! $subject197Passed && $this->subjectUndergoing(
            $subject197,
            $enrollments,
            $prospectus,
            $studentProfile,
        );
        $subject198Passed = $this->subjectPassed($subject198, $completedEnrollmentBySubject);

        $prospectusUnits = (float) ($academicView['dashboard']['prospectusUnits'] ?? 0.0);
        $earnedUnits = (float) ($academicView['dashboard']['earnedUnits'] ?? 0.0);
        $earnedUnitPercentage = $prospectusUnits > 0
            ? round(($earnedUnits / $prospectusUnits) * 100, 1)
            : null;

        $requirements = [
            [
                'label' => 'Fourth-year standing',
                'met' => (int) $studentProfile->year_level === 4,
                'detail' => 'Current year level: '.$studentProfile->year_level,
            ],
            [
                'label' => 'Passed '.($subject198?->subject_code ?? 'XXX198'),
                'met' => $subject198Passed,
                'detail' => $this->subjectRequirementDetail($subject198, $subject198Passed, false),
            ],
            [
                'label' => 'Passed or currently taking '.($subject197?->subject_code ?? 'XXX197'),
                'met' => $subject197Passed || $subject197Undergoing,
                'detail' => $this->subjectRequirementDetail($subject197, $subject197Passed, $subject197Undergoing),
            ],
            [
                'label' => 'Earned at least 90% of prospectus units',
                'met' => $earnedUnitPercentage !== null && $earnedUnitPercentage >= 90,
                'detail' => $prospectusUnits > 0
                    ? $this->formatUnits($earnedUnits).' / '.$this->formatUnits($prospectusUnits).' units ('.number_format($earnedUnitPercentage ?? 0, 1).'%)'
                    : 'Prospectus units are unavailable.',
            ],
        ];

        $eligible = collect($requirements)->every(fn (array $requirement): bool => $requirement['met'] === true);

        return [
            'eligible' => $eligible,
            'canMarkGraduating' => $eligible && ! $studentProfile->is_graduating,
            'alreadyGraduating' => (bool) $studentProfile->is_graduating,
            'earnedUnits' => $earnedUnits,
            'prospectusUnits' => $prospectusUnits,
            'earnedUnitPercentage' => $earnedUnitPercentage,
            'requirements' => $requirements,
        ];
    }

    /**
     * @return Collection<string, Subject|null>
     */
    private function graduationSubjects(?Prospectus $prospectus): Collection
    {
        if ($prospectus === null) {
            return collect([
                '197' => null,
                '198' => null,
            ]);
        }

        $subjects = $prospectus->terms
            ->flatMap(fn ($term) => $term->subjects)
            ->unique('id')
            ->values();

        return collect([
            '197' => $subjects->first(fn (Subject $subject): bool => $this->matchesGraduationSuffix($subject, '197')),
            '198' => $subjects->first(fn (Subject $subject): bool => $this->matchesGraduationSuffix($subject, '198')),
        ]);
    }

    private function matchesGraduationSuffix(Subject $subject, string $suffix): bool
    {
        return preg_match('/'.preg_quote($suffix, '/').'$/', trim($subject->subject_code)) === 1;
    }

    /**
     * @param  Collection<int, StudentSubjectEnrollment>  $completedEnrollmentBySubject
     */
    private function subjectPassed(?Subject $subject, Collection $completedEnrollmentBySubject): bool
    {
        if ($subject === null) {
            return false;
        }

        return $completedEnrollmentBySubject->has($subject->id);
    }

    /**
     * @param  Collection<int, StudentSubjectEnrollment>  $enrollments
     */
    private function subjectUndergoing(
        ?Subject $subject,
        Collection $enrollments,
        ?Prospectus $prospectus,
        StudentProfile $studentProfile,
    ): bool
    {
        if ($subject === null) {
            return false;
        }

        if ($enrollments
            ->where('subject_id', $subject->id)
            ->contains(fn (StudentSubjectEnrollment $enrollment): bool => in_array($this->normalizedEnrollmentStatus($enrollment), ['in_progress', 'grade_submitted'], true))) {
            return true;
        }

        $activeAcademicTerm = AcademicTerm::active();

        if ($activeAcademicTerm === null || $prospectus === null) {
            return false;
        }

        $currentProspectusTerm = $prospectus->terms->first(fn (ProspectusTerm $term): bool => $term->year_level === (int) $studentProfile->year_level
            && $term->term_name === $activeAcademicTerm->term_name);

        if ($currentProspectusTerm === null) {
            return false;
        }

        return $currentProspectusTerm->subjects->contains(fn (Subject $prospectusSubject): bool => (int) $prospectusSubject->id === (int) $subject->id);
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

    private function subjectRequirementDetail(?Subject $subject, bool $passed, bool $undergoing): string
    {
        if ($subject === null) {
            return 'No matching curriculum subject was found in the active prospectus.';
        }

        if ($passed) {
            return $subject->subject_code.' is already completed.';
        }

        if ($undergoing) {
            return $subject->subject_code.' is currently in progress.';
        }

        return $subject->subject_code.' is not yet satisfied.';
    }

    private function formatUnits(float $units): string
    {
        return rtrim(rtrim(number_format($units, 2, '.', ''), '0'), '.');
    }
}
