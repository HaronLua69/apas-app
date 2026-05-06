<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
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
    public function create(College $college): View
    {
        return view('admin.departments.create', [
            'college' => $college,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, College $college): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abbreviation' => ['nullable', 'string', 'max:50'],
            'chairperson' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $college->departments()->create($validated);

        return redirect()
            ->route('admin.colleges.show', $college)
            ->with('status', 'Department created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Department $department): View
    {
        $activeSection = $request->string('section')->toString();

        if (! in_array($activeSection, ['programs', 'subjects'], true)) {
            $activeSection = 'programs';
        }

        $department->load('college');

        if ($activeSection === 'programs') {
            $department->load('courses');
        }

        if ($activeSection === 'subjects') {
            $department->load('subjects.requisites.requisiteSubject');
        }

        return view('admin.departments.show', [
            'activeSection' => $activeSection,
            'department' => $department,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Department $department): View
    {
        return view('admin.departments.edit', [
            'department' => $department->load('college'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abbreviation' => ['nullable', 'string', 'max:50'],
            'chairperson' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $department->update($validated);

        return redirect()
            ->route('admin.departments.show', $department)
            ->with('status', 'Department updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department): RedirectResponse
    {
        $college = $department->college;

        $department->delete();

        return redirect()
            ->route('admin.colleges.show', $college)
            ->with('status', 'Department deleted successfully.');
    }
}
