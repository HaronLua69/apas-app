<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Major;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MajorController extends Controller
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
    public function create(Course $course): View
    {
        return view('admin.majors.create', [
            'course' => $course->load('department'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $course->majors()->create($validated);

        return redirect()
            ->route('admin.courses.show', $course)
            ->with('status', 'Major created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Major $major)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Major $major): View
    {
        return view('admin.majors.edit', [
            'major' => $major->load('course.department'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Major $major): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $major->update($validated);

        return redirect()
            ->route('admin.courses.show', $major->course)
            ->with('status', 'Major updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Major $major): RedirectResponse
    {
        $course = $major->course;

        $major->delete();

        return redirect()
            ->route('admin.courses.show', $course)
            ->with('status', 'Major deleted successfully.');
    }
}
