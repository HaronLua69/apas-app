<x-layouts::app :title="__('Advisees')">
    @php($formatGpa = fn (?float $gpa): string => $gpa === null ? 'N/A' : number_format($gpa, 5))
    @php($toggleDirection = fn (string $column): string => $sort === $column && $direction === 'asc' ? 'desc' : 'asc')

    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl p-6">
        <section class="rounded-3xl border border-zinc-200 bg-zinc-50 px-6 py-8 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">{{ __('Advising') }}</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">{{ __('Advisees') }}</h1>
            <p class="mt-3 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $activeAcademicTerm?->fullLabel() ?? __('No active academic term configured.') }}</p>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('Review the students currently bound to your advising assignments and open their student-equivalent advising tabs.') }}</p>
        </section>

        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-950/60">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('ID Number') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">
                            <a href="{{ route('adviser.students.index', ['sort' => 'name', 'direction' => $toggleDirection('name')]) }}" class="hover:text-zinc-900 dark:hover:text-zinc-100">{{ __('Name') }}</a>
                        </th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">
                            <a href="{{ route('adviser.students.index', ['sort' => 'gpa', 'direction' => $toggleDirection('gpa')]) }}" class="hover:text-zinc-900 dark:hover:text-zinc-100">{{ __('GPA') }}</a>
                        </th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">
                            <a href="{{ route('adviser.students.index', ['sort' => 'cgpa', 'direction' => $toggleDirection('cgpa')]) }}" class="hover:text-zinc-900 dark:hover:text-zinc-100">{{ __('Cumulative GPA') }}</a>
                        </th>
                        <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @forelse ($students as $student)
                        <tr>
                            <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ $student['studentProfile']->user->id_number }}</td>
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $student['displayName'] }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $formatGpa($student['previousSemesterGpa']) }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $formatGpa($student['cumulativeGpa']) }}</td>
                            <td class="px-4 py-3 text-right">
                                <flux:button size="sm" variant="primary" :href="route('adviser.students.show', $student['studentProfile'])" wire:navigate>
                                    {{ __('View') }}
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">{{ __('No students are currently bound to your advising assignments.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
</x-layouts::app>