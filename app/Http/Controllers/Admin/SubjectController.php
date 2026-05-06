<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ElectiveCategory;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubjectController extends Controller
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
        return view('admin.subjects.create', [
            'department' => $department,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Department $department): RedirectResponse
    {
        $request->merge([
            'counts_toward_gpa' => $request->boolean('counts_toward_gpa'),
        ]);

        $validated = $request->validate([
            'subject_code' => ['required', 'string', 'max:255', 'unique:subjects,subject_code'],
            'subject_title' => ['required', 'string', 'max:255'],
            'subject_types' => ['required', 'array', 'min:1'],
            'subject_types.*' => ['string', 'in:Lecture,Laboratory,Field,Seminar'],
            'credit_units' => ['required', 'numeric', 'min:0.5', 'max:30'],
            'grading_system' => ['required', 'string', 'in:numerical,letter'],
            'description' => ['nullable', 'string'],
            'counts_toward_gpa' => ['nullable', 'boolean'],
        ]);

        $department->subjects()->create($validated);

        return redirect()
            ->route('admin.departments.show', ['department' => $department, 'section' => 'subjects'])
            ->with('status', 'Subject created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Subject $subject): View
    {
        return view('admin.subjects.show', [
            'electiveCategoryOptions' => ElectiveCategory::options(),
            'subject' => $subject->load([
                'department.college',
                'department.courses.majors',
                'electiveScopes.course',
                'electiveScopes.major',
                'requisites.course',
                'requisites.major',
                'requisites.requisiteSubject.department.college',
            ]),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Subject $subject): View
    {
        return view('admin.subjects.edit', [
            'subject' => $subject->load('department'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $request->merge([
            'counts_toward_gpa' => $request->boolean('counts_toward_gpa'),
        ]);

        $validated = $request->validate([
            'subject_code' => ['required', 'string', 'max:255', 'unique:subjects,subject_code,'.$subject->id],
            'subject_title' => ['required', 'string', 'max:255'],
            'subject_types' => ['required', 'array', 'min:1'],
            'subject_types.*' => ['string', 'in:Lecture,Laboratory,Field,Seminar'],
            'credit_units' => ['required', 'numeric', 'min:0.5', 'max:30'],
            'grading_system' => ['required', 'string', 'in:numerical,letter'],
            'description' => ['nullable', 'string'],
            'counts_toward_gpa' => ['nullable', 'boolean'],
        ]);

        $subject->update($validated);

        return redirect()
            ->route('admin.subjects.show', $subject)
            ->with('status', 'Subject updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Subject $subject): RedirectResponse
    {
        $department = $subject->department;

        $subject->delete();

        return redirect()
            ->route('admin.departments.show', ['department' => $department, 'section' => 'subjects'])
            ->with('status', 'Subject deleted successfully.');
    }

    public function updateElectiveScopes(Request $request, Subject $subject): RedirectResponse
    {
        $subject->loadMissing('department.courses.majors');

        $validScopeKeys = $this->validElectiveScopeKeys($subject);
        $isElective = $request->boolean('is_elective');
        $scopeCategories = collect($request->input('elective_scope_categories', []));

        $request->merge([
            'is_elective' => $isElective,
        ]);

        $validated = $request->validate([
            'is_elective' => ['nullable', 'boolean'],
            'elective_scope_categories' => ['nullable', 'array'],
            'elective_scope_categories.*' => ['nullable', 'string', Rule::in(ElectiveCategory::values())],
        ]);

        $selectedScopeCategories = $scopeCategories
            ->filter(fn ($category, $scopeKey) => in_array((string) $scopeKey, $validScopeKeys->all(), true)
                && filled($category))
            ->map(fn ($category) => (string) $category);

        if ($isElective && $selectedScopeCategories->isEmpty()) {
            return back()
                ->withErrors(['elective_scope_categories' => 'Assign at least one elective category to a program or major scope.'])
                ->withInput();
        }

        $subject->electiveScopes()->delete();

        if ($isElective) {
            $selectedScopeCategories
                ->each(function (string $category, string $scopeKey) use ($subject): void {
                    [$courseId, $majorToken] = explode(':', $scopeKey, 2);

                    $subject->electiveScopes()->create([
                        'course_id' => (int) $courseId,
                        'major_id' => $majorToken === 'all' ? null : (int) $majorToken,
                        'category' => $category,
                    ]);
                });
        }

        return redirect()
            ->route('admin.subjects.show', $subject)
            ->with('status', 'Elective settings updated successfully.');
    }

    /**
     * @return Collection<int, string>
     */
    private function validElectiveScopeKeys(Subject $subject): Collection
    {
        return $subject->department->courses
            ->flatMap(function ($course): Collection {
                return collect([$this->electiveScopeKey($course->id, null)])
                    ->merge($course->majors->map(fn ($major) => $this->electiveScopeKey($course->id, $major->id)));
            })
            ->values();
    }

    private function electiveScopeKey(int $courseId, ?int $majorId): string
    {
        return $courseId.':'.($majorId ?? 'all');
    }
}
