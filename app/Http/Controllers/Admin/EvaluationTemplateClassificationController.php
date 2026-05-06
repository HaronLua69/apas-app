<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvaluationTemplate;
use App\Models\EvaluationTemplateClassification;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EvaluationTemplateClassificationController extends Controller
{
    public function create(EvaluationTemplate $evaluationTemplate): View
    {
        return view('admin.evaluation-template-classifications.create', [
            'evaluationTemplate' => $evaluationTemplate->load('course.department', 'major'),
            'availableSubjects' => $this->availableSubjects(),
        ]);
    }

    public function store(Request $request, EvaluationTemplate $evaluationTemplate): RedirectResponse
    {
        $validated = $this->validateClassification($request, $evaluationTemplate);
        $subjectIds = $this->selectedSubjectIds($validated);

        DB::transaction(function () use ($evaluationTemplate, $validated, $subjectIds): void {
            $classification = $evaluationTemplate->classifications()->create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'display_order' => $validated['display_order'],
            ]);

            $classification->subjects()->sync($this->subjectSyncPayload($subjectIds));
        });

        return redirect()
            ->route('admin.evaluation-templates.show', $evaluationTemplate)
            ->with('status', 'Classification created successfully.');
    }

    public function edit(EvaluationTemplateClassification $classification): View
    {
        return view('admin.evaluation-template-classifications.edit', [
            'classification' => $classification->load('evaluationTemplate.course.department', 'evaluationTemplate.major', 'subjects'),
            'availableSubjects' => $this->availableSubjects(),
        ]);
    }

    public function update(Request $request, EvaluationTemplateClassification $classification): RedirectResponse
    {
        $classification->loadMissing('evaluationTemplate.course.department');

        $validated = $this->validateClassification($request, $classification->evaluationTemplate);
        $subjectIds = array_key_exists('subject_ids', $validated)
            ? $this->selectedSubjectIds($validated)
            : null;

        DB::transaction(function () use ($classification, $validated, $subjectIds): void {
            $classification->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'display_order' => $validated['display_order'],
            ]);

            if ($subjectIds !== null) {
                $classification->subjects()->sync($this->subjectSyncPayload($subjectIds));
            }
        });

        return redirect()
            ->route('admin.evaluation-templates.show', $classification->evaluationTemplate)
            ->with('status', 'Classification updated successfully.');
    }

    public function destroy(EvaluationTemplateClassification $classification): RedirectResponse
    {
        $evaluationTemplate = $classification->evaluationTemplate;

        $classification->delete();

        return redirect()
            ->route('admin.evaluation-templates.show', $evaluationTemplate)
            ->with('status', 'Classification deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateClassification(Request $request, EvaluationTemplate $evaluationTemplate): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'display_order' => ['required', 'integer', 'min:1', 'max:999'],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['integer', 'distinct', 'exists:subjects,id'],
        ]);

        return $validated;
    }

    private function availableSubjects(): Collection
    {
        return Subject::query()
            ->with('department.college')
            ->orderBy('subject_code')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return Collection<int, int>
     */
    private function selectedSubjectIds(array $validated): Collection
    {
        return collect($validated['subject_ids'] ?? [])->map(fn ($id) => (int) $id)->values();
    }

    /**
     * @param  Collection<int, int>  $subjectIds
     * @return array<int, array<string, int>>
     */
    private function subjectSyncPayload(Collection $subjectIds): array
    {
        return $subjectIds
            ->values()
            ->mapWithKeys(fn ($subjectId, $index) => [
                $subjectId => ['display_order' => $index + 1],
            ])
            ->all();
    }
}
