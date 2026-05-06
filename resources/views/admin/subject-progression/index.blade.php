@php($formatCount = fn (int $count, string $singular, string $plural): string => $count.' '.($count === 1 ? $singular : $plural))

<x-admin.layout :title="__('Subject Progression')" :heading="__('Subject Progression')" :subheading="__('Trace prerequisite and co-requisite paths for a program, with optional major-specific filtering.')" :backHref="route('admin.dashboard')" :backLabel="__('Back to Dashboard')">
    <div class="space-y-4">
        <section class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
            <div class="flex items-start justify-between gap-4 max-md:flex-col max-md:items-start">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Graph Scope') }}</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Choose a program to visualize its requisite progression. Every terminal path converges into one graduation goal.') }}</p>
                </div>

                <div class="rounded-2xl bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:bg-zinc-950/60 dark:text-zinc-300">
                    <p>{{ __('Directed edge') }}: {{ __('Pre-requisite to post-requisite subject') }}</p>
                    <p class="mt-1">{{ __('Undirected edge') }}: {{ __('Subject to its co-requisite') }}</p>
                    <p class="mt-1">{{ __('Graduation Goal') }}: {{ __('The single degree outcome once all requirements are completed') }}</p>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.subject-progression.index') }}" class="mt-6 grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] lg:items-end">
                <flux:select name="course_id" :label="__('Program')" required>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected($selectedCourse?->id === $course->id)>
                            {{ $course->abbreviation ?: $course->name }}
                        </option>
                    @endforeach
                </flux:select>

                <flux:select name="major_id" :label="__('Major Filter (Optional)')">
                    <option value="">{{ __('All majors and general requisites') }}</option>
                    @foreach ($selectedCourse?->majors ?? [] as $major)
                        <option value="{{ $major->id }}" @selected($selectedMajor?->id === $major->id)>
                            {{ $major->name }}
                        </option>
                    @endforeach
                </flux:select>

                <flux:button type="submit" variant="primary">{{ __('View Progression') }}</flux:button>
            </form>
        </section>

        @if ($selectedCourse)
            <section class="grid gap-4 md:grid-cols-3">
                <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Components') }}</p>
                    <p class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-zinc-50">{{ $componentCount }}</p>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $formatCount($componentCount, 'path cluster', 'path clusters') }}</p>
                </article>
                <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Subjects') }}</p>
                    <p class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-zinc-50">{{ $nodeCount }}</p>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Nodes included in the current scope.') }}</p>
                </article>
                <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Requisite Links') }}</p>
                    <p class="mt-3 text-2xl font-semibold text-zinc-950 dark:text-zinc-50">{{ $edgeCount }}</p>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Directed and undirected subject requisites. Graduation links are rendered separately.') }}</p>
                </article>
            </section>

            <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-4 max-md:flex-col max-md:items-start">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $selectedCourse->abbreviation ?: $selectedCourse->name }}</h2>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $selectedCourse->name }}</p>
                    </div>

                    <div class="rounded-2xl bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:bg-zinc-950/60 dark:text-zinc-300">
                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Applied Scope') }}</p>
                        <p class="mt-1">{{ $selectedMajor?->name ?: __('All majors and general requisites') }}</p>
                    </div>
                </div>
            </section>
        @endif

        @if (! $selectedCourse)
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Create a program first before opening the subject progression module.') }}</p>
            </section>
        @elseif ($components->isEmpty())
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No subject progression graph is available for this scope yet. Add prerequisite or co-requisite links to see the graph.') }}</p>
            </section>
        @else
            <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-4 max-md:flex-col max-md:items-start">
                    <div>
                        <p class="text-sm font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Unified Progression Map') }}</p>
                        <h3 class="mt-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('All roads lead to graduation') }}</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __(':components track clusters feed into one shared degree outcome.', ['components' => $formatCount($componentCount, 'path cluster', 'path clusters')]) }}</p>
                    </div>

                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Hover any subject to highlight the progression map.') }}</p>
                </div>

                <div class="mt-5">
                    @include('partials.subject-progression-graph', [
                        'canvasHeight' => $canvasHeight,
                        'canvasWidth' => $canvasWidth,
                        'goal' => $goal,
                        'graphEdges' => $graphEdges,
                        'graphNodes' => $graphNodes,
                        'mode' => 'admin',
                    ])
                </div>
            </section>
        @endif
    </div>
</x-admin.layout>