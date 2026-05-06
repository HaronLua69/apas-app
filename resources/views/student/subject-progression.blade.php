<x-layouts::app :title="__('Subject Progression')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl p-6">
        <section class="rounded-3xl border border-zinc-200 bg-zinc-50 px-6 py-8 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-4 max-md:flex-col max-md:items-start">
                <div>
                    <p class="text-sm font-medium uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">{{ __('Student View') }}</p>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <h1 class="text-3xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">{{ __('Subject Progression') }}</h1>
                        @if ($studentProfile->is_graduating)
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-amber-800 dark:bg-amber-500/15 dark:text-amber-300">{{ __('Graduating') }}</span>
                        @endif
                    </div>
                    <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('Review your track-specific path to graduation. This graph only shows your major path, shared requirements, and one graduation cap.') }}</p>
                </div>

                <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-950/60">
                    <p class="text-zinc-500 dark:text-zinc-400">{{ __('Student') }}</p>
                    <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ $studentProfile->user->fullName() }}</p>
                    <p class="text-zinc-500 dark:text-zinc-400">{{ $studentProfile->user->id_number }}</p>
                </div>
            </div>
        </section>

        @if ($selectedCourse === null)
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No active admission is available for this student yet.') }}</p>
            </section>
        @elseif ($graphNodes === [])
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No subject progression graph is available for your current track yet.') }}</p>
            </section>
        @else
            <section class="grid gap-4 md:grid-cols-3">
                <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Program') }}</p>
                    <p class="mt-3 text-lg font-semibold text-zinc-950 dark:text-zinc-50">{{ $selectedCourse->abbreviation ?: $selectedCourse->name }}</p>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $selectedCourse->name }}</p>
                </article>
                <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Track') }}</p>
                    <p class="mt-3 text-lg font-semibold text-zinc-950 dark:text-zinc-50">{{ $selectedMajor?->name ?: __('General program') }}</p>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Colored edges mark your track-specific branches.') }}</p>
                </article>
                <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Node Details') }}</p>
                    <p class="mt-3 text-sm text-zinc-900 dark:text-zinc-100">{{ __('Each node shows only subject code, units, and your recorded grade.') }}</p>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('The graduation cap stays unchanged as the single degree outcome.') }}</p>
                </article>
            </section>

            @include('partials.subject-progression-graph', [
                'canvasHeight' => $canvasHeight,
                'canvasWidth' => $canvasWidth,
                'goal' => $goal,
                'graphEdges' => $graphEdges,
                'graphNodes' => $graphNodes,
                'mode' => 'student',
            ])
        @endif
    </div>
</x-layouts::app>