<x-admin.layout :title="__('Adviser Assignments')" :heading="$adviser->fullName()" :subheading="__('Manage program, major, and year-level assignments for this adviser.')">
    <div class="grid gap-4 lg:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
        <section class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
            <div>
                <p class="text-sm uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ $adviser->adviserProfile->department->abbreviation ?: $adviser->adviserProfile->department->name }}</p>
                <h2 class="mt-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Create Assignment') }}</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Assign this adviser to a program, optional major, and year level.') }}</p>
            </div>

            <form method="POST" action="{{ route('admin.advisers.assignments.store', $adviser) }}" class="mt-6 space-y-6">
                @csrf

                <flux:select name="course_id" :label="__('Program')" required>
                    <option value="">{{ __('Select a program') }}</option>
                    @foreach ($adviser->adviserProfile->department->courses as $course)
                        <option value="{{ $course->id }}" @selected((string) old('course_id') === (string) $course->id)>
                            {{ $course->name }}
                        </option>
                    @endforeach
                </flux:select>

                <flux:select name="major_id" :label="__('Major')">
                    <option value="">{{ __('All majors / general assignment') }}</option>
                    @foreach ($adviser->adviserProfile->department->courses as $course)
                        @foreach ($course->majors as $major)
                            <option value="{{ $major->id }}" @selected((string) old('major_id') === (string) $major->id)>
                                {{ $course->abbreviation ?: $course->name }} · {{ $major->name }}
                            </option>
                        @endforeach
                    @endforeach
                </flux:select>

                <flux:input name="year_level" :label="__('Year Level')" :value="old('year_level', 1)" type="number" min="1" max="8" required />

                <div class="flex items-center justify-end gap-3">
                    <flux:button :href="route('admin.advisers.index')" variant="ghost" wire:navigate>{{ __('Back to Advisers') }}</flux:button>
                    <flux:button type="submit" variant="primary">{{ __('Save Assignment') }}</flux:button>
                </div>
            </form>
        </section>

        <section class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Current Assignments') }}</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Review the active advising scope for this adviser.') }}</p>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($adviser->adviserProfile->adviserAssignments as $assignment)
                    <article class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $assignment->course->name }}</p>
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $assignment->major?->name ?: __('General program assignment') }}</p>
                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Year Level') }} {{ $assignment->year_level }}</p>
                            </div>

                            <form method="POST" action="{{ route('admin.adviser-assignments.destroy', $assignment) }}">
                                @csrf
                                @method('DELETE')
                                <flux:button size="sm" variant="danger" type="submit">{{ __('Remove') }}</flux:button>
                            </form>
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No adviser assignments have been created yet.') }}</p>
                @endforelse
            </div>
        </section>
    </div>
</x-admin.layout>