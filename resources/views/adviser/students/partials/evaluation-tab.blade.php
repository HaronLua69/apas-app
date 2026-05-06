@php($formatUnits = fn (float $units): string => rtrim(rtrim(number_format($units, 2, '.', ''), '0'), '.'))
@php($formatSummaryUnits = fn (float $units, bool $isNstp): string => $isNstp ? '('.$formatUnits($units).')' : $formatUnits($units))
@php($summaryRows = $evaluationProgress->sortBy(fn (array $classificationProgress) => [$classificationProgress['summarySortOrder'], $classificationProgress['classification']->display_order])->values())
@php($nonNstpSummaryRows = $summaryRows->filter(fn (array $classificationProgress) => ! $classificationProgress['isNstp']))
@php($totalRequiredUnits = (float) $nonNstpSummaryRows->sum('requiredUnits'))
@php($totalEarnedUnits = (float) $nonNstpSummaryRows->sum('earnedUnits'))

@if (! $evaluationTemplate)
    <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No evaluation template matches this student yet.') }}</p>
    </section>
@else
    <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <p class="text-sm uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ $evaluationTemplate->course->abbreviation ?: $evaluationTemplate->course->name }}</p>
        <h2 class="mt-2 text-lg font-semibold text-zinc-950 dark:text-zinc-50">{{ $evaluationTemplate->title }}</h2>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $evaluationTemplate->description ?: __('No description provided.') }}</p>
    </section>

    <section class="grid gap-4 md:grid-cols-3">
        <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Template Subjects') }}</p>
            <p class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-zinc-50">{{ $evaluationSummary['totalSubjects'] }}</p>
        </article>
        <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Recorded Grades') }}</p>
            <p class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-zinc-50">{{ $evaluationSummary['recordedGrades'] }}</p>
        </article>
        <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Pending Subjects') }}</p>
            <p class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-zinc-50">{{ $evaluationSummary['pendingSubjects'] }}</p>
        </article>
    </section>

    <div class="space-y-4">
        @foreach ($evaluationProgress as $classificationProgress)
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-4 max-md:flex-col max-md:items-start">
                    <div>
                        <p class="text-sm font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Order') }} {{ $classificationProgress['classification']->display_order }}</p>
                        <h2 class="mt-2 text-lg font-semibold text-zinc-950 dark:text-zinc-50">{{ $classificationProgress['classification']->name }}</h2>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $classificationProgress['classification']->description ?: __('No description provided.') }}</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl bg-zinc-50 px-4 py-3 text-sm dark:bg-zinc-950/60">
                            <p class="text-zinc-500 dark:text-zinc-400">{{ __('Completed Subjects') }}</p>
                            <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ $classificationProgress['completedCount'] }}/{{ $classificationProgress['totalSubjects'] }}</p>
                        </div>
                        <div class="rounded-2xl bg-zinc-50 px-4 py-3 text-sm dark:bg-zinc-950/60">
                            <p class="text-zinc-500 dark:text-zinc-400">{{ __('Recorded Grades') }}</p>
                            <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ $classificationProgress['recordedCount'] }}</p>
                        </div>
                        <div class="rounded-2xl bg-zinc-50 px-4 py-3 text-sm dark:bg-zinc-950/60">
                            <p class="text-zinc-500 dark:text-zinc-400">{{ __('Earned Units') }}</p>
                            <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ $formatUnits($classificationProgress['earnedUnits']) }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/60">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Subject Code') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Subject Title') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Units') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Grade') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Completion') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-transparent">
                            @foreach ($classificationProgress['subjectRows'] as $subjectRow)
                                <tr class="{{ $subjectRow['completed'] ? 'bg-emerald-50/80 dark:bg-emerald-500/10' : '' }}">
                                    <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $subjectRow['subject']->subject_code }}</td>
                                    <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $subjectRow['subject']->subject_title }}</td>
                                    <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $formatUnits((float) $subjectRow['subject']->credit_units) }}</td>
                                    <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $subjectRow['grade'] !== '' ? $subjectRow['grade'] : '—' }}</td>
                                    <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                        @if ($subjectRow['completed'])
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">{{ __('Completed') }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    </div>

    <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="space-y-1">
            <p class="text-sm font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Evaluation Summary') }}</p>
            <h2 class="text-lg font-semibold text-zinc-950 dark:text-zinc-50">{{ __('Summary of Units') }}</h2>
        </div>

        <div class="mt-4 overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900/60">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Classification') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Required') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Earned') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-transparent">
                    @foreach ($summaryRows as $classificationProgress)
                        <tr>
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $classificationProgress['classification']->name }}</td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $formatSummaryUnits($classificationProgress['requiredUnits'], $classificationProgress['isNstp']) }}</td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $formatSummaryUnits($classificationProgress['earnedUnits'], $classificationProgress['isNstp']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="bg-zinc-50 dark:bg-zinc-900/60">
                        <td class="px-4 py-3 font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Total') }}</td>
                        <td class="px-4 py-3 font-semibold text-zinc-900 dark:text-zinc-100">{{ $formatUnits($totalRequiredUnits) }}</td>
                        <td class="px-4 py-3 font-semibold text-zinc-900 dark:text-zinc-100">{{ $formatUnits($totalEarnedUnits) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
@endif