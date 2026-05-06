<x-admin.layout :title="$department->name" :heading="$department->name" :subheading="__('Manage programs and subjects under this department.')" :backHref="route('admin.colleges.show', $department->college)" :backLabel="__('Back to College')">
    <div class="grid gap-4 lg:grid-cols-[15rem_minmax(0,1fr)]">
        <aside class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
            <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Department Sections') }}</p>

            <nav class="mt-4 space-y-2">
                <a
                    href="{{ route('admin.departments.show', ['department' => $department, 'section' => 'programs']) }}"
                    class="flex items-center justify-between rounded-xl px-3 py-2 text-sm font-medium transition {{ $activeSection === 'programs' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800' }}"
                >
                    <span>{{ __('Programs') }}</span>
                    <span class="text-xs opacity-75">{{ $department->courses->count() }}</span>
                </a>

                <a
                    href="{{ route('admin.departments.show', ['department' => $department, 'section' => 'subjects']) }}"
                    class="flex items-center justify-between rounded-xl px-3 py-2 text-sm font-medium transition {{ $activeSection === 'subjects' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800' }}"
                >
                    <span>{{ __('Subjects') }}</span>
                    <span class="text-xs opacity-75">{{ $department->subjects->count() }}</span>
                </a>
            </nav>
        </aside>

        <section class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
            @if ($activeSection === 'programs')
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Programs') }}</h2>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Review program entries, then open each one to manage majors and other program details.') }}</p>
                    </div>

                    <flux:button :href="route('admin.departments.courses.create', $department)" size="sm" variant="primary" wire:navigate>
                        {{ __('Add Program') }}
                    </flux:button>
                </div>

                <div class="mt-4 overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/60">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Abbreviation') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Program Name') }}</th>
                                <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">{{ __('Option') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-transparent">
                            @forelse ($department->courses as $course)
                                <tr>
                                    <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $course->abbreviation ?: '—' }}</td>
                                    <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $course->name }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <flux:button size="sm" variant="ghost" :href="route('admin.courses.show', $course)" wire:navigate>{{ __('View') }}</flux:button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">{{ __('No programs have been created for this department yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Subjects') }}</h2>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Review subject entries, then open each one to manage descriptions and actions.') }}</p>
                    </div>

                    <flux:button :href="route('admin.departments.subjects.create', $department)" size="sm" variant="primary" wire:navigate>
                        {{ __('Add Subject') }}
                    </flux:button>
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
                                <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">{{ __('Option') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-transparent">
                            @forelse ($department->subjects as $subject)
                                @php($prerequisites = $subject->requisites->where('type', 'prerequisite')->map(fn ($requisite) => $requisite->requisiteSubject?->subject_code)->filter()->implode(', '))
                                @php($corequisites = $subject->requisites->where('type', 'corequisite')->map(fn ($requisite) => $requisite->requisiteSubject?->subject_code)->filter()->implode(', '))
                                <tr>
                                    <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $subject->subject_code }}</td>
                                    <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $subject->subject_title }}</td>
                                    <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ rtrim(rtrim(number_format((float) $subject->credit_units, 2, '.', ''), '0'), '.') }}</td>
                                    <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $prerequisites !== '' ? $prerequisites : '—' }}</td>
                                    <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $corequisites !== '' ? $corequisites : '—' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <flux:button size="sm" variant="ghost" :href="route('admin.subjects.show', $subject)" wire:navigate>{{ __('View') }}</flux:button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">{{ __('No subjects have been created for this department yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-admin.layout>