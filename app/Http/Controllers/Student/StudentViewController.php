<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\StudentProfile;
use App\Models\User;
use App\Support\StudentAcademicView;
use App\Support\SubjectProgressionGraph;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class StudentViewController extends Controller
{
    public function __construct(
        public StudentAcademicView $studentAcademicView,
        public SubjectProgressionGraph $subjectProgressionGraph,
    ) {}

    public function dashboard(Request $request): View
    {
        return view('student.dashboard', $this->pageData($request));
    }

    public function programOfStudy(Request $request): View
    {
        return view('student.program-of-study', $this->pageData($request));
    }

    public function prospectus(Request $request): View
    {
        return view('student.prospectus', $this->pageData($request));
    }

    public function evaluation(Request $request): View
    {
        return view('student.evaluation', $this->pageData($request));
    }

    public function subjectProgression(Request $request): View
    {
        $studentProfile = $this->studentProfile($request);
        $pageData = $this->pageDataForProfile($studentProfile);
        $activeAdmission = $studentProfile->activeAdmission;

        $graph = $activeAdmission?->course === null
            ? $this->emptyGraph()
            : $this->subjectProgressionGraph->build($activeAdmission->course, $activeAdmission->major, [
                'gradeLabels' => $this->gradeLabels($pageData['enrollments']),
            ]);

        return view('student.subject-progression', [
            ...$pageData,
            'selectedCourse' => $activeAdmission?->course,
            'selectedMajor' => $activeAdmission?->major,
            ...$graph,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function pageData(Request $request): array
    {
        $studentProfile = $this->studentProfile($request);

        return $this->pageDataForProfile($studentProfile);
    }

    /**
     * @return array<string, mixed>
     */
    private function pageDataForProfile(StudentProfile $studentProfile): array
    {
        $studentProfile->loadMissing(
            'activeAdmission.course.majors',
            'activeAdmission.major',
            'adviserBinding.adviserAssignment.adviserProfile.user',
        );

        return [
            'studentProfile' => $studentProfile,
            ...$this->studentAcademicView->build($studentProfile),
        ];
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

    private function studentProfile(Request $request): StudentProfile
    {
        /** @var User $student */
        $student = $request->user();

        $student->loadMissing('studentProfile');

        abort_unless($student->studentProfile !== null, 404);

        return $student->studentProfile;
    }
}