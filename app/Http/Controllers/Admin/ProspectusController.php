<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Major;
use App\Models\Prospectus;
use App\Support\ProspectusPrerequisiteChecker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProspectusController extends Controller
{
    public function duplicate(Prospectus $prospectus): View
    {
        return view('admin.prospectuses.duplicate', [
            'course' => $prospectus->course->load('department', 'majors'),
            'sourceProspectus' => $prospectus->load('major'),
        ]);
    }

    public function create(Course $course): View
    {
        return view('admin.prospectuses.create', [
            'course' => $course->load('department', 'majors'),
        ]);
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $validated = $this->validateProspectus($request, $course);
        $isActive = $request->boolean('is_active');

        $prospectus = DB::transaction(function () use ($course, $validated, $isActive): Prospectus {
            if ($isActive) {
                $course->prospectuses()
                    ->where('major_id', $validated['major_id'] ?? null)
                    ->update(['is_active' => false]);
            }

            return $course->prospectuses()->create([
                'major_id' => $validated['major_id'] ?? null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'is_active' => $isActive,
            ]);
        });

        return redirect()
            ->route('admin.prospectuses.show', $prospectus)
            ->with('status', 'Prospectus created successfully.');
    }

    public function show(ProspectusPrerequisiteChecker $checker, Prospectus $prospectus): View
    {
        $prospectus->load([
            'course.department',
            'major',
            'terms.electives',
            'terms.subjects.requisites.requisiteSubject',
        ]);

        $electiveNumbers = [];
        $electiveCounter = 1;

        foreach ($prospectus->terms as $term) {
            foreach ($term->electives as $elective) {
                $electiveNumbers[$elective->id] = $electiveCounter;
                $electiveCounter++;
            }
        }

        return view('admin.prospectuses.show', [
            'electiveNumbers' => $electiveNumbers,
            'prospectus' => $prospectus,
            'prerequisiteIssues' => $checker->check($prospectus),
        ]);
    }

    public function edit(Prospectus $prospectus): View
    {
        return view('admin.prospectuses.edit', [
            'prospectus' => $prospectus->load('course.department', 'course.majors', 'major'),
        ]);
    }

    public function update(Request $request, Prospectus $prospectus): RedirectResponse
    {
        $prospectus->loadMissing('course');

        $validated = $this->validateProspectus($request, $prospectus->course);
        $isActive = $request->boolean('is_active');

        DB::transaction(function () use ($prospectus, $validated, $isActive): void {
            if ($isActive) {
                Prospectus::query()
                    ->where('course_id', $prospectus->course_id)
                    ->where('major_id', $validated['major_id'] ?? null)
                    ->whereKeyNot($prospectus->id)
                    ->update(['is_active' => false]);
            }

            $prospectus->update([
                'major_id' => $validated['major_id'] ?? null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'is_active' => $isActive,
            ]);
        });

        return redirect()
            ->route('admin.prospectuses.show', $prospectus)
            ->with('status', 'Prospectus updated successfully.');
    }

    public function destroy(Prospectus $prospectus): RedirectResponse
    {
        $department = $prospectus->course->department;

        $prospectus->delete();

        return redirect()
            ->route('admin.departments.show', $department)
            ->with('status', 'Prospectus deleted successfully.');
    }

    public function storeDuplicate(Request $request, Prospectus $prospectus): RedirectResponse
    {
        $prospectus->load([
            'course',
            'terms.subjects',
            'terms.electives',
        ]);

        $validated = $this->validateProspectus($request, $prospectus->course);
        $isActive = $request->boolean('is_active');

        $duplicate = DB::transaction(function () use ($prospectus, $validated, $isActive): Prospectus {
            if ($isActive) {
                $prospectus->course->prospectuses()
                    ->where('major_id', $validated['major_id'] ?? null)
                    ->update(['is_active' => false]);
            }

            $duplicate = $prospectus->course->prospectuses()->create([
                'major_id' => $validated['major_id'] ?? null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'is_active' => $isActive,
            ]);

            foreach ($prospectus->terms as $term) {
                $duplicateTerm = $duplicate->terms()->create([
                    'year_level' => $term->year_level,
                    'term_name' => $term->term_name,
                    'display_order' => $term->display_order,
                    'total_units' => $term->total_units,
                ]);

                $duplicateTerm->subjects()->attach(
                    $term->subjects->mapWithKeys(fn ($subject) => [
                        $subject->id => ['display_order' => $subject->pivot->display_order],
                    ])->all()
                );

                $duplicateTerm->electives()->createMany(
                    $term->electives->map(fn ($elective) => [
                        'name' => $elective->name,
                        'category' => $elective->category?->value,
                        'units' => $elective->units,
                        'display_order' => $elective->display_order,
                    ])->all()
                );
            }

            return $duplicate;
        });

        return redirect()
            ->route('admin.prospectuses.show', $duplicate)
            ->with('status', 'Prospectus duplicated successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProspectus(Request $request, Course $course): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'major_id' => ['nullable', 'integer', 'exists:majors,id'],
            'description' => ['nullable', 'string'],
        ]);

        if (($validated['major_id'] ?? null) !== null) {
            $major = Major::query()->findOrFail($validated['major_id']);

            abort_unless($major->course_id === $course->id, 422);
        }

        return $validated;
    }
}
