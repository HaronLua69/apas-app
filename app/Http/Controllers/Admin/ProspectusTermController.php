<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ElectiveCategory;
use App\Http\Controllers\Controller;
use App\Models\Prospectus;
use App\Models\ProspectusTerm;
use App\Models\ProspectusTermElective;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProspectusTermController extends Controller
{
    private const TERM_OPTIONS = [
        '1st Semester',
        '2nd Semester',
        'Summer Term',
    ];

    public function create(Prospectus $prospectus): View
    {
        return view('admin.prospectus-terms.create', [
            'electiveCategoryOptions' => ElectiveCategory::options(),
            'prospectus' => $prospectus->load('course.department', 'major'),
            'availableSubjects' => $this->availableSubjects(),
        ]);
    }

    public function store(Request $request, Prospectus $prospectus): RedirectResponse
    {
        $validated = $this->validateTerm($request, $prospectus);
        $subjectIds = $validated['subject_ids'] ?? [];
        $electiveEntries = $validated['elective_entries'] ?? [];

        DB::transaction(function () use ($prospectus, $validated, $subjectIds, $electiveEntries): void {
            $electiveUnits = count($electiveEntries) * 3;

            $term = $prospectus->terms()->create([
                'year_level' => $validated['year_level'],
                'term_name' => $validated['term_name'],
                'display_order' => $this->termDisplayOrder($validated['term_name']),
                'total_units' => Subject::query()->whereKey($subjectIds)->sum('credit_units') + $electiveUnits,
            ]);

            $term->subjects()->sync($this->subjectSyncPayload($subjectIds));
            $this->syncElectives($term, $electiveEntries);
        });

        return redirect()
            ->route('admin.prospectuses.show', $prospectus)
            ->with('status', 'Semestral distribution created successfully.');
    }

    public function edit(ProspectusTerm $prospectusTerm): View
    {
        $prospectusTerm->load('prospectus.course.department', 'prospectus.major', 'subjects', 'electives');

        abort_unless($prospectusTerm->prospectus !== null, 404);

        return view('admin.prospectus-terms.edit', [
            'electiveCategoryOptions' => ElectiveCategory::options(),
            'prospectus' => $prospectusTerm->prospectus,
            'prospectusTerm' => $prospectusTerm,
            'availableSubjects' => $this->availableSubjects(),
        ]);
    }

    public function update(Request $request, ProspectusTerm $prospectusTerm): RedirectResponse
    {
        $prospectusTerm->loadMissing('prospectus.course.department');

        abort_unless($prospectusTerm->prospectus !== null, 404);

        $validated = $this->validateTerm($request, $prospectusTerm->prospectus, $prospectusTerm);
        $subjectIds = $validated['subject_ids'] ?? [];
        $electiveEntries = $validated['elective_entries'] ?? [];

        DB::transaction(function () use ($prospectusTerm, $validated, $subjectIds, $electiveEntries): void {
            $electiveUnits = count($electiveEntries) * 3;

            $prospectusTerm->update([
                'year_level' => $validated['year_level'],
                'term_name' => $validated['term_name'],
                'display_order' => $this->termDisplayOrder($validated['term_name']),
                'total_units' => Subject::query()->whereKey($subjectIds)->sum('credit_units') + $electiveUnits,
            ]);

            $prospectusTerm->subjects()->sync($this->subjectSyncPayload($subjectIds));
            $this->syncElectives($prospectusTerm, $electiveEntries);
        });

        return redirect()
            ->route('admin.prospectuses.show', $prospectusTerm->prospectus)
            ->with('status', 'Semestral distribution updated successfully.');
    }

    public function destroy(ProspectusTerm $prospectusTerm): RedirectResponse
    {
        $prospectus = $prospectusTerm->prospectus;

        abort_unless($prospectus !== null, 404);

        $prospectusTerm->delete();

        return redirect()
            ->route('admin.prospectuses.show', $prospectus)
            ->with('status', 'Semestral distribution deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTerm(Request $request, Prospectus $prospectus, ?ProspectusTerm $prospectusTerm = null): array
    {
        $validated = $request->validate([
            'year_level' => ['required', 'integer', 'min:1', 'max:4'],
            'term_name' => [
                'required',
                'string',
                Rule::in(self::TERM_OPTIONS),
                Rule::unique('prospectus_terms')
                    ->where(fn ($query) => $query
                        ->where('prospectus_id', $prospectus->id)
                        ->where('year_level', (int) $request->input('year_level')))
                    ->ignore($prospectusTerm),
            ],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['integer', 'distinct', 'exists:subjects,id'],
            'elective_names' => ['nullable', 'array'],
            'elective_names.*' => ['string', 'max:255', 'distinct'],
            'elective_categories' => ['nullable', 'array'],
            'elective_categories.*' => ['nullable', 'string', Rule::in(ElectiveCategory::values())],
        ]);

        $subjectIds = collect($validated['subject_ids'] ?? [])->filter();
        $electiveNames = collect($validated['elective_names'] ?? [])->map(fn ($name) => trim((string) $name));
        $electiveCategories = collect($request->input('elective_categories', []));
        $electiveEntries = $electiveNames
            ->map(function (string $name, int $index) use ($electiveCategories): ?array {
                $trimmedName = trim($name);

                if ($trimmedName === '') {
                    return null;
                }

                $category = $electiveCategories->get($index);

                if (! filled($category)) {
                    $category = ElectiveCategory::inferFromName($trimmedName)->value;
                }

                return [
                    'name' => $trimmedName,
                    'category' => (string) $category,
                ];
            })
            ->filter();

        if ($subjectIds->isEmpty() && $electiveEntries->isEmpty()) {
            throw ValidationException::withMessages([
                'subject_ids' => __('Add at least one subject or elective placeholder.'),
            ]);
        }

        $validated['subject_ids'] = $subjectIds->values()->all();
        $validated['elective_entries'] = $electiveEntries->values()->all();

        return $validated;
    }

    private function termDisplayOrder(string $termName): int
    {
        return match ($termName) {
            '1st Semester' => 1,
            '2nd Semester' => 2,
            'Summer Term' => 3,
            default => 99,
        };
    }

    private function availableSubjects()
    {
        return Subject::query()
            ->with('department.college')
            ->orderBy('subject_code')
            ->get();
    }

    /**
     * @param  array<int, int|string>  $subjectIds
     * @return array<int, array<string, int>>
     */
    private function subjectSyncPayload(array $subjectIds): array
    {
        return collect($subjectIds)
            ->values()
            ->mapWithKeys(fn ($subjectId, $index) => [
                (int) $subjectId => ['display_order' => $index + 1],
            ])
            ->all();
    }

    /**
     * @param  array<int, array{name:string,category:string}>  $electiveEntries
     */
    private function syncElectives(ProspectusTerm $prospectusTerm, array $electiveEntries): void
    {
        $prospectusTerm->electives()->delete();

        $payload = collect($electiveEntries)
            ->values()
            ->map(fn (array $entry, int $index) => [
                'name' => $entry['name'],
                'category' => $entry['category'],
                'units' => 3,
                'display_order' => $index + 1,
            ])
            ->all();

        if ($payload !== []) {
            $prospectusTerm->electives()->createMany($payload);
        }
    }
}
