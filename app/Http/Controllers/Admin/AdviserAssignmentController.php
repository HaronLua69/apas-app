<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdviserAssignment;
use App\Models\Major;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdviserAssignmentController extends Controller
{
    public function index(User $adviser): View
    {
        abort_unless($adviser->isAdviser(), 404);

        return view('admin.users.advisers.assignments', [
            'adviser' => $adviser->load([
                'adviserProfile.department.courses.majors',
                'adviserProfile.adviserAssignments.course',
                'adviserProfile.adviserAssignments.major',
            ]),
        ]);
    }

    public function store(Request $request, User $adviser): RedirectResponse
    {
        abort_unless($adviser->isAdviser(), 404);

        $adviser->loadMissing('adviserProfile.department.courses.majors');

        $validated = $request->validate([
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')],
            'major_id' => ['nullable', 'integer', Rule::exists('majors', 'id')],
            'year_level' => ['required', 'integer', 'min:1', 'max:8'],
        ]);

        $departmentCourseIds = $adviser->adviserProfile->department->courses->pluck('id');
        abort_unless($departmentCourseIds->contains((int) $validated['course_id']), 422);

        if (($validated['major_id'] ?? null) !== null) {
            $major = Major::query()->findOrFail($validated['major_id']);

            abort_unless($major->course_id === (int) $validated['course_id'], 422);
        }

        $duplicateAssignmentExists = AdviserAssignment::query()
            ->where('adviser_profile_id', $adviser->adviserProfile->id)
            ->where('course_id', $validated['course_id'])
            ->where('year_level', $validated['year_level'])
            ->when(
                ($validated['major_id'] ?? null) === null,
                fn ($query) => $query->whereNull('major_id'),
                fn ($query) => $query->where('major_id', $validated['major_id'])
            )
            ->exists();

        if ($duplicateAssignmentExists) {
            return back()
                ->withErrors(['course_id' => 'This adviser assignment already exists.'])
                ->withInput();
        }

        $adviser->adviserProfile->adviserAssignments()->create([
            'course_id' => $validated['course_id'],
            'major_id' => $validated['major_id'] ?? null,
            'year_level' => $validated['year_level'],
        ]);

        return redirect()
            ->route('admin.advisers.assignments.index', $adviser)
            ->with('status', 'Adviser assignment created successfully.');
    }

    public function destroy(AdviserAssignment $adviserAssignment): RedirectResponse
    {
        $adviser = $adviserAssignment->adviserProfile->user;

        if ($adviserAssignment->studentAdviserBindings()->exists()) {
            return back()->withErrors([
                'adviser_assignment' => 'Reassign or clear bound students before removing this adviser assignment.',
            ]);
        }

        $adviserAssignment->delete();

        return redirect()
            ->route('admin.advisers.assignments.index', $adviser)
            ->with('status', 'Adviser assignment removed successfully.');
    }
}
