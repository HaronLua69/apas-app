<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Major;
use App\Models\Subject;
use App\Models\SubjectRequisite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubjectRequisiteController extends Controller
{
    public function index(Subject $subject): View
    {
        $subject->load('department.courses.majors');

        return view('admin.subjects.requisites', [
            'subject' => $subject->load(
                'department',
                'requisites.requisiteSubject.department.college',
                'requisites.course',
                'requisites.major'
            ),
            'availableSubjects' => Subject::query()
                ->whereKeyNot($subject->id)
                ->with('department.college')
                ->orderBy('subject_code')
                ->get(),
            'availableScopes' => $this->availableScopes($subject),
        ]);
    }

    public function store(Request $request, Subject $subject): RedirectResponse
    {
        $subject->loadMissing('department.courses.majors');

        $validated = $request->validate([
            'requisite_subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')->whereNot('id', $subject->id)],
            'type' => ['required', 'string', Rule::in(['prerequisite', 'corequisite'])],
            'scope_key' => ['required', 'string', Rule::in($this->availableScopes($subject)->pluck('key')->all())],
        ]);

        [$courseId, $majorId] = $this->parseScopeKey($validated['scope_key']);

        $validated['course_id'] = $courseId;
        $validated['major_id'] = $majorId;

        $exists = SubjectRequisite::query()
            ->where('subject_id', $subject->id)
            ->where('requisite_subject_id', $validated['requisite_subject_id'])
            ->where('type', $validated['type'])
            ->where('course_id', $validated['course_id'])
            ->where('major_id', $validated['major_id'])
            ->exists();

        if ($exists) {
            return back()
                ->withErrors(['requisite_subject_id' => 'This subject requisite already exists.'])
                ->withInput();
        }

        $subject->requisites()->create($validated);

        return redirect()
            ->route('admin.subjects.requisites.index', $subject)
            ->with('status', 'Subject requisite added successfully.');
    }

    public function destroy(SubjectRequisite $subjectRequisite): RedirectResponse
    {
        $subject = $subjectRequisite->subject;

        $subjectRequisite->delete();

        return redirect()
            ->route('admin.subjects.requisites.index', $subject)
            ->with('status', 'Subject requisite removed successfully.');
    }

    /**
     * @return Collection<int, array{key:string,label:string}>
     */
    private function availableScopes(Subject $subject): Collection
    {
        return collect([
            [
                'key' => 'all',
                'label' => 'All programs and majors',
            ],
        ])->merge(
            $subject->department->courses->flatMap(function (Course $course): Collection {
                return collect([
                    [
                        'key' => 'course:'.$course->id,
                        'label' => $course->name,
                    ],
                ])->merge(
                    $course->majors->map(fn (Major $major) => [
                        'key' => 'major:'.$major->id,
                        'label' => $course->name.' - Major in '.$major->name,
                    ])
                );
            })
        )->values();
    }

    /**
     * @return array{0:?int,1:?int}
     */
    private function parseScopeKey(string $scopeKey): array
    {
        if ($scopeKey === 'all') {
            return [null, null];
        }

        [$type, $identifier] = explode(':', $scopeKey, 2);

        if ($type === 'course') {
            return [(int) $identifier, null];
        }

        $major = Major::query()->findOrFail((int) $identifier);

        return [$major->course_id, $major->id];
    }
}
