<?php

namespace App\Http\Controllers\Admin;

use App\Models\Course;
use App\Http\Controllers\Controller;
use App\Support\SubjectProgressionGraph;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectProgressionController extends Controller
{
    public function __construct(public SubjectProgressionGraph $subjectProgressionGraph) {}

    public function index(Request $request): View
    {
        $courses = Course::query()
            ->with('department.college', 'majors')
            ->orderBy('name')
            ->get();

        $requestedCourseId = $request->integer('course_id');
        $selectedCourse = $requestedCourseId > 0
            ? $courses->firstWhere('id', $requestedCourseId)
            : $courses->first();
        $requestedMajorId = $request->integer('major_id');
        $selectedMajor = $selectedCourse !== null && $requestedMajorId > 0
            ? $selectedCourse->majors->firstWhere('id', $requestedMajorId)
            : null;

        $graph = $selectedCourse === null
            ? [
                'components' => collect(),
                'componentCount' => 0,
                'edgeCount' => 0,
                'nodeCount' => 0,
            ]
            : $this->subjectProgressionGraph->build($selectedCourse, $selectedMajor);

        return view('admin.subject-progression.index', [
            'courses' => $courses,
            'selectedCourse' => $selectedCourse,
            'selectedMajor' => $selectedMajor,
            ...$graph,
        ]);
    }
}
