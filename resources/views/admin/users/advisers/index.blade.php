<x-admin.layout :title="__('Advisers')" :heading="__('Advisers')" :subheading="__('Review adviser accounts, departments, and profile information.')">
    <div class="flex items-center justify-end">
        <flux:button :href="route('admin.advisers.create')" variant="primary" wire:navigate>
            {{ __('Add Adviser') }}
        </flux:button>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-950/60">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('ID Number') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Department') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Rank') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Email') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse ($advisers as $adviser)
                    <tr>
                        <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ $adviser->id_number }}</td>
                        <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ $adviser->fullName() }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $adviser->adviserProfile?->department?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $adviser->adviserProfile?->rank ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $adviser->email }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <flux:button size="sm" variant="subtle" :href="route('admin.advisers.assignments.index', $adviser)" wire:navigate>
                                    {{ __('Assignments') }}
                                </flux:button>

                                <flux:button size="sm" variant="subtle" :href="route('admin.advisers.edit', $adviser)" wire:navigate>
                                    {{ __('Edit') }}
                                </flux:button>

                                <form method="POST" action="{{ route('admin.advisers.destroy', $adviser) }}">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button size="sm" variant="danger" type="submit">{{ __('Delete') }}</flux:button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('No adviser accounts have been created yet.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin.layout>