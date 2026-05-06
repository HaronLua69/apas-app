<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\EvaluationTemplate;
use App\Models\Major;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluationTemplateController extends Controller
{
    public function create(Course $course): View
    {
        return view('admin.evaluation-templates.create', [
            'course' => $course->load('department', 'majors'),
        ]);
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $validated = $this->validateTemplate($request, $course);

        $evaluationTemplate = $course->evaluationTemplates()->create([
            'major_id' => $validated['major_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('admin.evaluation-templates.show', $evaluationTemplate)
            ->with('status', 'Evaluation template created successfully.');
    }

    public function show(EvaluationTemplate $evaluationTemplate): View
    {
        return view('admin.evaluation-templates.show', [
            'evaluationTemplate' => $evaluationTemplate->load([
                'course.department',
                'major',
                'classifications.subjects',
            ]),
        ]);
    }

    public function edit(EvaluationTemplate $evaluationTemplate): View
    {
        return view('admin.evaluation-templates.edit', [
            'evaluationTemplate' => $evaluationTemplate->load('course.department', 'course.majors', 'major'),
        ]);
    }

    public function update(Request $request, EvaluationTemplate $evaluationTemplate): RedirectResponse
    {
        $evaluationTemplate->loadMissing('course');

        $validated = $this->validateTemplate($request, $evaluationTemplate->course);

        $evaluationTemplate->update([
            'major_id' => $validated['major_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('admin.evaluation-templates.show', $evaluationTemplate)
            ->with('status', 'Evaluation template updated successfully.');
    }

    public function destroy(EvaluationTemplate $evaluationTemplate): RedirectResponse
    {
        $department = $evaluationTemplate->course->department;

        $evaluationTemplate->delete();

        return redirect()
            ->route('admin.departments.show', $department)
            ->with('status', 'Evaluation template deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTemplate(Request $request, Course $course): array
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
