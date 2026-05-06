<x-layouts::app :title="__('APAP Report')">
    @php($formatRate = fn (?float $value): string => $value === null ? 'N/A' : number_format($value, 2).'%')
    @php($formatGpa = fn (?float $value): string => $value === null ? 'N/A' : number_format($value, 5))
    @php($selectedCourse = $courseOptions->firstWhere('id', $filters['course_id']))
    @php($selectedMajor = $majorOptions->firstWhere('id', $filters['major_id']))
    @php($metrics = [
        ['label' => __('Total Program Enrollees'), 'value' => $report['totalProgramEnrollees']],
        ['label' => __('Survival Rate'), 'value' => $formatRate($report['survivalRate'])],
        ['label' => __('Completion Rate'), 'value' => $formatRate($report['completionRate'])],
        ['label' => __('Promotion Rate'), 'value' => $formatRate($report['promotionRate'])],
        ['label' => __('Failure Rate'), 'value' => $formatRate($report['failureRate'])],
        ['label' => __('Dropout Rate'), 'value' => $formatRate($report['dropoutRate'])],
        ['label' => __('Average Academic Year GPA'), 'value' => $formatGpa($report['averageAcademicYearGpa'])],
        ['label' => __('Average CGPA of Students'), 'value' => $formatGpa($report['averageCumulativeGpa'])],
        ['label' => __('Number of Students with INC'), 'value' => $report['studentsWithInc']],
        ['label' => __('Number of Students who withdrew from the program'), 'value' => $report['studentsWithdrawn']],
        ['label' => __('Number of Students with failing grades'), 'value' => $report['studentsWithFailingGrades']],
        ['label' => __('Rizal’s Excellence Awardees'), 'value' => $report['rizalAwardees']],
        ['label' => __('Chancellor’s Excellence Awardees'), 'value' => $report['chancellorAwardees']],
        ['label' => __('Dean’s Excellence Awardees'), 'value' => $report['deanAwardees']],
    ])

    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl p-6">
        <section class="rounded-3xl border border-zinc-200 bg-zinc-50 px-6 py-8 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">{{ __('Advising Analytics') }}</p>
            <div class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <h1 class="text-3xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">{{ __('Academic Program Advising Progress (APAP) Report') }}</h1>
                    <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('Review end-of-academic-year advising outcomes across your advisee cohort, including cohort shrinkage, completion, progression, GPA, INC exposure, withdrawals, and awardees.') }}</p>
                </div>

                <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-950/60">
                    <p class="text-zinc-500 dark:text-zinc-400">{{ __('Current Scope') }}</p>
                    <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Academic Year: :year', ['year' => $filters['academic_year']]) }}</p>
                    <p class="text-zinc-500 dark:text-zinc-400">{{ $selectedCourse?->name ?? __('All Courses') }}{{ $selectedMajor ? ' · '.$selectedMajor->name : '' }}{{ $filters['year_level'] ? ' · '.__('Year :level', ['level' => $filters['year_level']]) : ' · '.__('All Year Levels') }}</p>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <form method="GET" action="{{ route('adviser.reports.apap') }}" class="grid gap-4 lg:grid-cols-4">
                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    <span>{{ __('Academic Year') }}</span>
                    <select name="academic_year" class="rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm outline-none transition focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                        @foreach ($academicYearOptions as $academicYearOption)
                            <option value="{{ $academicYearOption }}" @selected($filters['academic_year'] === $academicYearOption)>{{ $academicYearOption }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    <span>{{ __('Course') }}</span>
                    <select name="course_id" class="rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm outline-none transition focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                        <option value="">{{ __('All Courses') }}</option>
                        @foreach ($courseOptions as $course)
                            <option value="{{ $course->id }}" @selected($filters['course_id'] === $course->id)>{{ $course->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    <span>{{ __('Major') }}</span>
                    <select name="major_id" class="rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm outline-none transition focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                        <option value="">{{ __('All Majors') }}</option>
                        @foreach ($majorOptions as $major)
                            <option value="{{ $major->id }}" @selected($filters['major_id'] === $major->id)>{{ $major->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="grid gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    <span>{{ __('Year Level') }}</span>
                    <select name="year_level" class="rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm outline-none transition focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                        <option value="">{{ __('All Year Levels') }}</option>
                        @foreach ($yearLevelOptions as $yearLevelOption)
                            <option value="{{ $yearLevelOption }}" @selected($filters['year_level'] === $yearLevelOption)>{{ __('Year :level', ['level' => $yearLevelOption]) }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="lg:col-span-4 flex flex-wrap items-center gap-3">
                    <flux:button type="submit" variant="primary">{{ __('Apply Filters') }}</flux:button>
                    <flux:button :href="route('adviser.reports.apap')" variant="ghost" wire:navigate>{{ __('Reset') }}</flux:button>
                    <flux:button :href="route('adviser.reports.apap.export', array_filter([
                        'academic_year' => $filters['academic_year'],
                        'course_id' => $filters['course_id'],
                        'major_id' => $filters['major_id'],
                        'year_level' => $filters['year_level'],
                    ], fn ($value) => $value !== null && $value !== ''))" variant="ghost">
                        {{ __('Export CSV') }}
                    </flux:button>
                </div>
            </form>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($metrics as $metric)
                <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ $metric['label'] }}</p>
                    <p class="mt-3 text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">{{ $metric['value'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-950 dark:text-zinc-50">{{ __('Cohort Detail') }}</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('The cohort denominator follows the latest enrolled term in the selected academic year, so end-of-year rates and averages reflect the actual advisee count at reporting time.') }}</p>
            </div>
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-950/60">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Student') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('AY GPA') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('End-of-AY GPA') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('CGPA') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Flags') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Award') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @forelse ($report['rows'] as $row)
                        <tr>
                            <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">
                                <div class="font-medium">{{ $row['displayName'] }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $row['studentProfile']->user?->id_number }}</div>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $formatGpa($row['academicYearGpa']) }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $formatGpa($row['endOfAcademicYearGpa']) }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $formatGpa($row['cumulativeGpa']) }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                                {{ collect([
                                    $row['countsAsCompleted'] ? __('Completed') : __('Incomplete'),
                                    $row['promoted'] ? __('Promoted') : __('Not Promoted'),
                                    $row['droppedAnySubject'] ? __('Dropped Subject') : null,
                                    $row['hasUnresolvedInc'] ? __('Has INC') : null,
                                    $row['withdrewFromProgram'] ? __('Withdrew') : null,
                                    $row['hasFailingGrade'] ? __('Failing Grade') : null,
                                ])->filter()->implode(' · ') }}
                            </td>
                            <td class="px-4 py-3 text-zinc-600 capitalize dark:text-zinc-300">{{ $row['award'] ?? __('None') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">{{ __('No advisees matched the selected APAP scope.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
</x-layouts::app>