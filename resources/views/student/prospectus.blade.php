@php($yearLevelLabels = [1 => __('First Year'), 2 => __('Second Year'), 3 => __('Third Year'), 4 => __('Fourth Year')])
@php($formatUnits = fn (float $units): string => rtrim(rtrim(number_format($units, 2, '.', ''), '0'), '.'))
@php($electiveNumber = 1)

<x-layouts::app :title="__('Prospectus')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl p-6">
        <section class="rounded-3xl border border-zinc-200 bg-zinc-50 px-6 py-8 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">{{ __('Student View') }}</p>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <h1 class="text-3xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">{{ __('Prospectus') }}</h1>
                @if ($studentProfile->is_graduating)
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-amber-800 dark:bg-amber-500/15 dark:text-amber-300">{{ __('Graduating') }}</span>
                @endif
            </div>
            <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('Review your active prospectus and see which required subjects are already completed.') }}</p>
        </section>

        @if (! $prospectus)
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No active prospectus matches this student yet.') }}</p>
            </section>
        @else
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center gap-3">
                    @if ($prospectus->is_active)
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">{{ __('Active') }}</span>
                    @endif
                    <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $prospectus->title }}</p>
                </div>

                <div class="mt-6 space-y-4">
                    @foreach ($prospectus->terms as $term)
                        @php($hasEntries = $term->electives->isNotEmpty() || $term->subjects->isNotEmpty())
                        <article class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <p class="text-sm font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ $yearLevelLabels[$term->year_level] ?? __('Year :year', ['year' => $term->year_level]) }}, {{ $term->term_name }}</p>

                            <div class="mt-4 overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700">
                                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                                    <thead class="bg-zinc-50 dark:bg-zinc-900/60">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Subject Code') }}</th>
                                            <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Subject Title') }}</th>
                                            <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Units') }}</th>
                                            <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Pre-Requisite') }}</th>
                                            <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Co-Requisite') }}</th>
                                            <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Completion') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-transparent">
                                        @foreach ($term->electives as $elective)
                                            @php($placement = $electivePlacementIndex->get($term->id, collect())->firstWhere('elective.id', $elective->id))
                                            <tr class="{{ ($placement['fulfilledBy'] ?? null) ? 'bg-emerald-50/80 dark:bg-emerald-500/10' : '' }}">
                                                <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ __('Elective :number', ['number' => $electiveNumber++]) }}</td>
                                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                                    {{ $elective->name }}
                                                    @if (($placement['fulfilledBy'] ?? null) !== null)
                                                        <p class="mt-1 text-xs uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-300">{{ $placement['fulfilledBy']->subject->subject_code }} · {{ $placement['fulfilledBy']->subject->subject_title }}</p>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $formatUnits((float) $elective->units) }}</td>
                                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $placement['prerequisites'] ?? __('As the course requires') }}</td>
                                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $placement['corequisites'] ?? __('As the course requires') }}</td>
                                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                                    @if (($placement['fulfilledBy'] ?? null) !== null)
                                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">{{ __('Completed') }}</span>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach

                                        @foreach ($term->subjects as $subject)
                                            @php($prerequisites = $subject->applicableRequisites($prospectus->course_id, $prospectus->major_id, 'prerequisite')->map(fn ($requisite) => $requisite->requisiteSubject?->subject_code)->filter()->implode(', '))
                                            @php($corequisites = $subject->applicableRequisites($prospectus->course_id, $prospectus->major_id, 'corequisite')->map(fn ($requisite) => $requisite->requisiteSubject?->subject_code)->filter()->implode(', '))
                                            @php($completed = $completedSubjectIds->contains($subject->id))
                                            <tr class="{{ $completed ? 'bg-emerald-50/80 dark:bg-emerald-500/10' : '' }}">
                                                <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $subject->subject_code }}</td>
                                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $subject->subject_title }}</td>
                                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $subject->counts_toward_gpa ? $formatUnits((float) $subject->credit_units) : '('.$formatUnits((float) $subject->credit_units).')' }}</td>
                                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $prerequisites !== '' ? $prerequisites : '—' }}</td>
                                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $corequisites !== '' ? $corequisites : '—' }}</td>
                                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                                    @if ($completed)
                                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">{{ __('Completed') }}</span>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach

                                        @if (! $hasEntries)
                                            <tr>
                                                <td colspan="6" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">{{ __('No subjects assigned to this term yet.') }}</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts::app>