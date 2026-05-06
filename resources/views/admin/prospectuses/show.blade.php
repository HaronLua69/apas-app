@php($yearLevelLabels = [1 => __('First Year'), 2 => __('Second Year'), 3 => __('Third Year'), 4 => __('Fourth Year')])
@php($formatUnits = fn (float $units): string => rtrim(rtrim(number_format($units, 2, '.', ''), '0'), '.'))

<x-admin.layout :title="$prospectus->title" :heading="$prospectus->title" :subheading="__('Manage terms and assigned subjects for this prospectus.')" :backHref="route('admin.departments.show', $prospectus->course->department)" :backLabel="__('Back to Department')">
    <div class="space-y-4">
        <section class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
            <div class="flex items-start justify-between gap-3 max-md:flex-col max-md:items-start">
                <div class="space-y-3 text-sm text-zinc-700 dark:text-zinc-200">
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Prospectus Overview') }}</span>
                        @if ($prospectus->is_active)
                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">{{ __('Active') }}</span>
                        @endif
                    </div>

                    <p><span class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Program:') }}</span> {{ $prospectus->course->name }}</p>
                    <p>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Major:') }}</span>
                        {{ $prospectus->major ? __('Major in :major', ['major' => $prospectus->major->name]) : __('General program prospectus') }}
                    </p>
                    <p><span class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Semestral Distributions:') }}</span> {{ $prospectus->terms->count() }}</p>
                </div>

                <div class="flex items-center gap-2 max-sm:flex-wrap">
                    <flux:button :href="route('admin.prospectuses.duplicate', $prospectus)" variant="ghost" wire:navigate>{{ __('Duplicate Prospectus') }}</flux:button>
                    <flux:button :href="route('admin.prospectuses.edit', $prospectus)" variant="primary" wire:navigate>{{ __('Edit Prospectus') }}</flux:button>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Semestral Distribution of Subjects') }}</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Review each year-level term distribution and manage assigned subjects below.') }}</p>
                </div>

                <flux:button :href="route('admin.prospectuses.terms.create', $prospectus)" size="sm" variant="primary" wire:navigate>
                    {{ __('Add Distribution') }}
                </flux:button>
            </div>

            <div class="mt-4 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Prerequisite Sequence Check') }}</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('A prerequisite must appear in an earlier term than the subject that requires it.') }}</p>
                    </div>

                    <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ count($prerequisiteIssues) === 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' }}">
                        {{ count($prerequisiteIssues) === 0 ? __('No issues found') : trans_choice(':count issue detected|:count issues detected', count($prerequisiteIssues), ['count' => count($prerequisiteIssues)]) }}
                    </span>
                </div>

                @if (count($prerequisiteIssues) > 0)
                    <div class="mt-3 space-y-3">
                        @foreach ($prerequisiteIssues as $issue)
                            <div class="rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:bg-amber-950/30 dark:text-amber-200">
                                <p class="font-medium">{{ $issue['subject'] }}</p>
                                <p class="mt-1">{{ $issue['message'] }}</p>
                                <p class="mt-1 text-xs uppercase tracking-[0.2em] text-amber-600 dark:text-amber-300">{{ $issue['placement'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="mt-4 space-y-4">
                @forelse ($prospectus->terms as $term)
                    @php($electiveUnits = (float) $term->electives->sum('units'))
                    @php($gpaUnits = (float) $term->subjects->where('counts_toward_gpa', true)->sum('credit_units') + $electiveUnits)
                    @php($allUnits = (float) $term->subjects->sum('credit_units') + $electiveUnits)
                    @php($hasNonGpaSubjects = $term->subjects->contains(fn ($subject) => ! $subject->counts_toward_gpa))
                    @php($hasEntries = $term->electives->isNotEmpty() || $term->subjects->isNotEmpty())
                    <article class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="flex items-start justify-between gap-3 max-md:flex-col max-md:items-start">
                            <div>
                                <p class="text-sm font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ $yearLevelLabels[$term->year_level] ?? __('Year :year', ['year' => $term->year_level]) }}, {{ $term->term_name }}</p>
                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ __('Total Units: :gpa', ['gpa' => $formatUnits($hasNonGpaSubjects ? $gpaUnits : $allUnits)]) }}@if ($hasNonGpaSubjects) ({{ $formatUnits($allUnits) }}) @endif
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <a
                                    href="{{ route('admin.terms.edit', $term) }}"
                                    wire:navigate
                                    class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-900"
                                >
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path d="M13.586 3.586a2 2 0 1 1 2.828 2.828l-8.75 8.75-3.664.836.836-3.664 8.75-8.75Z" />
                                    </svg>
                                    <span>{{ __('Edit') }}</span>
                                </a>

                                <form method="POST" action="{{ route('admin.terms.destroy', $term) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="inline-flex items-center gap-2 rounded-xl border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 transition hover:bg-rose-50 dark:border-rose-900 dark:text-rose-300 dark:hover:bg-rose-950/40"
                                    >
                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M8.75 2.5A1.25 1.25 0 0 0 7.5 3.75v.25H5.75a.75.75 0 0 0 0 1.5h.443l.853 10.236A2.25 2.25 0 0 0 9.289 17.5h1.422a2.25 2.25 0 0 0 2.243-2.014l.853-10.236h.443a.75.75 0 0 0 0-1.5H12.5v-.25A1.25 1.25 0 0 0 11.25 2.5h-2.5Zm2.25 1.5v-.25a.25.25 0 0 0-.25-.25h-2.5a.25.25 0 0 0-.25.25V4h3ZM8.5 7.25a.75.75 0 0 1 .75.75v5a.75.75 0 0 1-1.5 0V8a.75.75 0 0 1 .75-.75Zm3 .75a.75.75 0 0 0-1.5 0v5a.75.75 0 0 0 1.5 0V8Z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ __('Delete') }}</span>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="mt-4 overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700">
                            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                                <thead class="bg-zinc-50 dark:bg-zinc-900/60">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Course Code') }}</th>
                                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Course Title') }}</th>
                                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Units') }}</th>
                                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Pre-Requisite') }}</th>
                                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Co-Requisite') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-transparent">
                                    @foreach ($term->electives as $elective)
                                        <tr>
                                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ __('Elective :number', ['number' => $electiveNumbers[$elective->id] ?? '?']) }}</td>
                                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $elective->name }}</td>
                                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $formatUnits((float) $elective->units) }}</td>
                                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ __('As the course requires') }}</td>
                                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ __('As the course requires') }}</td>
                                        </tr>
                                    @endforeach

                                    @foreach ($term->subjects as $subject)
                                        @php($prerequisites = $subject->applicableRequisites($prospectus->course_id, $prospectus->major_id, 'prerequisite')->map(fn ($requisite) => $requisite->requisiteSubject?->subject_code)->filter()->implode(', '))
                                        @php($corequisites = $subject->applicableRequisites($prospectus->course_id, $prospectus->major_id, 'corequisite')->map(fn ($requisite) => $requisite->requisiteSubject?->subject_code)->filter()->implode(', '))
                                        <tr>
                                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $subject->subject_code }}</td>
                                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $subject->subject_title }}</td>
                                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $subject->counts_toward_gpa ? $formatUnits((float) $subject->credit_units) : '('.$formatUnits((float) $subject->credit_units).')' }}</td>
                                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $prerequisites !== '' ? $prerequisites : '—' }}</td>
                                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $corequisites !== '' ? $corequisites : '—' }}</td>
                                        </tr>
                                    @endforeach

                                    @if (! $hasEntries)
                                        <tr>
                                            <td colspan="5" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">{{ __('No subjects assigned to this term yet.') }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No semestral distributions have been added to this prospectus yet.') }}</p>
                @endforelse
            </div>
        </section>
    </div>
</x-admin.layout>