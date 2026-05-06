<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AcademicTermController extends Controller
{
    public function index(): View
    {
        return view('admin.academic-terms.index', [
            'academicTerms' => AcademicTerm::query()->ordered()->get(),
            'activeAcademicTerm' => AcademicTerm::active(),
        ]);
    }

    public function create(): View
    {
        return view('admin.academic-terms.create');
    }

    public function store(Request $request): RedirectResponse
    {
        AcademicTerm::query()->create($this->validatedPayload($request));

        return redirect()
            ->route('admin.academic-terms.index')
            ->with('status', 'Academic term created successfully.');
    }

    public function edit(AcademicTerm $academicTerm): View
    {
        return view('admin.academic-terms.edit', [
            'academicTerm' => $academicTerm,
        ]);
    }

    public function update(Request $request, AcademicTerm $academicTerm): RedirectResponse
    {
        $academicTerm->update($this->validatedPayload($request, $academicTerm));

        return redirect()
            ->route('admin.academic-terms.index')
            ->with('status', 'Academic term updated successfully.');
    }

    public function destroy(AcademicTerm $academicTerm): RedirectResponse
    {
        $academicTerm->delete();

        return redirect()
            ->route('admin.academic-terms.index')
            ->with('status', 'Academic term deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, ?AcademicTerm $academicTerm = null): array
    {
        $validated = $request->validate([
            'academic_year_start' => [
                'required',
                'integer',
                'digits:4',
                'min:2000',
                'max:9998',
                Rule::unique(AcademicTerm::class, 'academic_year_start')
                    ->where(fn ($query) => $query->where('term_name', $request->input('term_name')))
                    ->ignore($academicTerm?->id),
            ],
            'term_name' => ['required', 'string', Rule::in(AcademicTerm::TERM_OPTIONS)],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
        ]);

        $overlapExists = AcademicTerm::query()
            ->when($academicTerm !== null, fn ($query) => $query->whereKeyNot($academicTerm->id))
            ->whereDate('date_from', '<=', $validated['date_to'])
            ->whereDate('date_to', '>=', $validated['date_from'])
            ->exists();

        if ($overlapExists) {
            throw ValidationException::withMessages([
                'date_from' => 'This academic term overlaps an existing academic term date range.',
            ]);
        }

        return $validated;
    }
}
