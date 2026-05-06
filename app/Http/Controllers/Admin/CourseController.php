<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Department $department): View
    {
        return view('admin.courses.create', [
            'department' => $department,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abbreviation' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        $department->courses()->create($validated);

        return redirect()
            ->route('admin.departments.show', ['department' => $department, 'section' => 'programs'])
            ->with('status', 'Program created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Course $course): View
    {
        return view('admin.courses.show', [
            'course' => $course->load([
                'department.college',
                'majors',
                'prospectuses.major',
                'evaluationTemplates.major',
            ]),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Course $course): View
    {
        return view('admin.courses.edit', [
            'course' => $course->load('department'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abbreviation' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        $course->update($validated);

        return redirect()
            ->route('admin.courses.show', $course)
            ->with('status', 'Program updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course): RedirectResponse
    {
        $department = $course->department;

        $course->delete();

        return redirect()
            ->route('admin.departments.show', ['department' => $department, 'section' => 'programs'])
            ->with('status', 'Program deleted successfully.');
    }
}
