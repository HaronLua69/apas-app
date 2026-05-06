@php($formatUnits = fn (float $units): string => rtrim(rtrim(number_format($units, 2, '.', ''), '0'), '.'))
@php($formatGpa = fn (?float $gpa): string => $gpa === null ? 'N/A' : number_format($gpa, 5))

<x-layouts::app :title="__('Program of Study')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl p-6">
        <section class="rounded-3xl border border-zinc-200 bg-zinc-50 px-6 py-8 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">{{ __('Student View') }}</p>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <h1 class="text-3xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">{{ __('Program of Study') }}</h1>
                @if ($studentProfile->is_graduating)
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-amber-800 dark:bg-amber-500/15 dark:text-amber-300">{{ __('Graduating') }}</span>
                @endif
            </div>
            <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('Review your actual academic years, terms, year levels, and the recorded grades for each scheduled subject.') }}</p>
            <p class="mt-4 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Cumulative GPA: :gpa', ['gpa' => $formatGpa($programOfStudySummary['cumulativeGpa'] ?? null)]) }}</p>
        </section>

        @if ($programOfStudy->isEmpty())
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No active prospectus is available for this student yet.') }}</p>
            </section>
        @else
            <div class="space-y-6">
                @foreach ($programOfStudy as $schoolYearGroup)
                    <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="space-y-1">
                            <h2 class="text-xl font-semibold text-zinc-950 dark:text-zinc-50">{{ __('Academic Year :schoolYear', ['schoolYear' => $schoolYearGroup['schoolYear']]) }}</h2>
                            <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Year Level: :yearLevel', ['yearLevel' => $schoolYearGroup['yearLevel']]) }}</p>
                        </div>

                        <div class="mt-6 space-y-6">
                            @foreach ($schoolYearGroup['terms'] as $termData)
                                <article>
                                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ $termData['term']->term_name }}</h3>
                                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Semester GPA: :gpa', ['gpa' => $formatGpa($termData['semesterGpa'] ?? null)]) }}</p>
                                    </div>

                                    <div class="mt-3 overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700">
                                        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                                            <thead class="bg-zinc-50 dark:bg-zinc-900/60">
                                                <tr>
                                                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Subject Code') }}</th>
                                                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Subject Title') }}</th>
                                                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Units') }}</th>
                                                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Pre-Requisite') }}</th>
                                                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Co-Requisite') }}</th>
                                                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Grade') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-transparent">
                                                @foreach ($termData['rows'] as $row)
                                                    <tr class="{{ $row['completed'] ? 'bg-emerald-50/80 dark:bg-emerald-500/10' : ($row['failed'] ? 'bg-rose-50/80 dark:bg-rose-500/10' : '') }}">
                                                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $row['code'] }}</td>
                                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                                            {{ $row['title'] }}
                                                            @if (($row['fulfilledBy'] ?? null) !== null)
                                                                <p class="mt-1 text-xs uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-300">{{ $row['fulfilledBy']->subject->subject_code }} · {{ $row['fulfilledBy']->subject->subject_title }}</p>
                                                            @endif
                                                            @if ($row['isRetaken'])
                                                                <p class="mt-2 text-xs font-semibold uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">{{ __('Retake #:attempt', ['attempt' => $row['attemptNumber'] ?? count($row['attemptHistory'])]) }}</p>
                                                                <div class="mt-1 space-y-1 text-xs text-zinc-500 dark:text-zinc-400">
                                                                    @foreach ($row['attemptHistory'] as $attempt)
                                                                        <p>{{ __('Attempt :attempt · :schoolYear · :term · :result', ['attempt' => $attempt['attemptNumber'], 'schoolYear' => $attempt['schoolYear'], 'term' => $attempt['termName'], 'result' => $attempt['result']]) }}</p>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $formatUnits($row['units']) }}</td>
                                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $row['prerequisites'] }}</td>
                                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $row['corequisites'] }}</td>
                                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                                            @if ($row['grade'] !== '')
                                                                {{ $row['grade'] }}
                                                                @if (($row['gradeContext'] ?? null) !== null)
                                                                    <p class="mt-1 text-xs font-medium uppercase tracking-[0.16em] text-zinc-500 dark:text-zinc-400">{{ $row['gradeContext'] }}</p>
                                                                @endif
                                                            @elseif (($row['status'] ?? null) === 'in_progress')
                                                                <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-medium text-sky-800 dark:bg-sky-500/15 dark:text-sky-300">{{ __('In Progress') }}</span>
                                                            @elseif (($row['status'] ?? null) === 'grade_submitted')
                                                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800 dark:bg-amber-500/15 dark:text-amber-300">{{ __('Grade Submitted') }}</span>
                                                            @else
                                                                —
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts::app>