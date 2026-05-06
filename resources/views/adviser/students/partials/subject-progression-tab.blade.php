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