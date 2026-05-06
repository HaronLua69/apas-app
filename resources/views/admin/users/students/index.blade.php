<x-admin.layout :title="__('Students')" :heading="__('Students')" :subheading="__('Review student accounts and their current profile information.')">
    <div class="flex items-center justify-end">
        <flux:button :href="route('admin.students.create')" variant="primary" wire:navigate>
            {{ __('Add Student') }}
        </flux:button>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-950/60">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('ID Number') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Adviser') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Username') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Course') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Year Level') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Email') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse ($students as $student)
                    <tr>
                        <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ $student->id_number }}</td>
                        <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ $student->fullName() }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $student->studentProfile?->adviserBinding?->adviserAssignment?->adviserProfile?->user?->fullName() ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ '@'.$student->username }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $student->studentProfile?->activeAdmission?->course?->abbreviation ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $student->studentProfile?->year_level ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $student->email }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <flux:button size="sm" variant="subtle" :href="route('admin.students.edit', $student)" wire:navigate>
                                    {{ __('Edit') }}
                                </flux:button>

                                <form method="POST" action="{{ route('admin.students.destroy', $student) }}">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button size="sm" variant="danger" type="submit">{{ __('Delete') }}</flux:button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('No student accounts have been created yet.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin.layout>