@php($formatUnits = fn (float $units): string => rtrim(rtrim(number_format($units, 2, '.', ''), '0'), '.'))
@php($earnedUnitsChart = [
    'type' => 'pie',
    'data' => [
        'labels' => [__('Earned Units'), __('Unearned Units')],
        'datasets' => [[
            'data' => [$dashboard['earnedUnits'], $dashboard['unearnedUnits']],
            'backgroundColor' => ['#15803d', '#d4d4d8'],
            'borderColor' => ['#166534', '#a1a1aa'],
            'borderWidth' => 1,
        ]],
    ],
])
@php($gradeDistributionChart = [
    'type' => 'line',
    'data' => [
        'labels' => $dashboard['gradeDistribution']->pluck('grade')->all(),
        'datasets' => [[
            'label' => __('Subjects'),
            'data' => $dashboard['gradeDistribution']->pluck('count')->all(),
            'borderColor' => '#0f766e',
            'backgroundColor' => 'rgba(15, 118, 110, 0.14)',
            'fill' => true,
            'tension' => 0.28,
        ]],
    ],
])
@php($adviser = $studentProfile->boundAdviser())

<x-layouts::app :title="__('Student Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl p-6">
        <section class="rounded-3xl border border-zinc-200 bg-zinc-50 px-6 py-8 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-4 max-md:flex-col max-md:items-start">
                <div>
                    <p class="text-sm font-medium uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">{{ __('Student View') }}</p>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <h1 class="text-3xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">{{ __('Academic Dashboard') }}</h1>
                        @if ($studentProfile->is_graduating)
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-amber-800 dark:bg-amber-500/15 dark:text-amber-300">{{ __('Graduating') }}</span>
                        @endif
                    </div>
                    <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('Track earned units, grade distribution, and your current academic standing from the active APAS records.') }}</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-950/60">
                        <p class="text-zinc-500 dark:text-zinc-400">{{ __('Student') }}</p>
                        <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ $studentProfile->user->fullName() }}</p>
                        <p class="text-zinc-500 dark:text-zinc-400">{{ $studentProfile->user->id_number }}</p>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-950/60">
                        <p class="text-zinc-500 dark:text-zinc-400">{{ __('Program Adviser') }}</p>
                        @if ($adviser)
                            <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ $adviser->fullName() }}</p>
                            <p class="text-zinc-500 dark:text-zinc-400">{{ $adviser->id_number }}</p>
                        @else
                            <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Not yet assigned') }}</p>
                            <p class="text-zinc-500 dark:text-zinc-400">{{ __('Your adviser will appear here once a binding is assigned.') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-4">
            <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Earned Units') }}</p>
                <p class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-zinc-50">{{ $formatUnits($dashboard['earnedUnits']) }}</p>
            </article>
            <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Unearned Units') }}</p>
                <p class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-zinc-50">{{ $formatUnits($dashboard['unearnedUnits']) }}</p>
            </article>
            <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Prospectus Units') }}</p>
                <p class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-zinc-50">{{ $formatUnits($dashboard['prospectusUnits']) }}</p>
            </article>
            <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Recorded Grades') }}</p>
                <p class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-zinc-50">{{ $evaluationSummary['recordedGrades'] }}</p>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
            <article class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-950 dark:text-zinc-50">{{ __('Earned vs Unearned Units') }}</h2>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Pie graph of completed units against the remaining units in your active prospectus.') }}</p>
                    </div>
                </div>

                <div class="mt-6 h-72">
                    <canvas data-chart-config='@json($earnedUnitsChart)' class="size-full"></canvas>
                </div>
            </article>

            <article class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-950 dark:text-zinc-50">{{ __('Grade Distribution') }}</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Line graph showing how many recorded subjects fall under each grade value.') }}</p>
                </div>

                <div class="mt-6 h-72">
                    <canvas data-chart-config='@json($gradeDistributionChart)' class="size-full"></canvas>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    @forelse ($dashboard['gradeDistribution'] as $gradeCount)
                        <div class="rounded-2xl bg-zinc-50 px-4 py-3 text-sm dark:bg-zinc-950/60">
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $gradeCount['grade'] }}</p>
                            <p class="mt-1 text-zinc-600 dark:text-zinc-300">{{ trans_choice(':count subject|:count subjects', $gradeCount['count'], ['count' => $gradeCount['count']]) }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No recorded grades yet.') }}</p>
                    @endforelse
                </div>
            </article>
        </section>
    </div>
</x-layouts::app>