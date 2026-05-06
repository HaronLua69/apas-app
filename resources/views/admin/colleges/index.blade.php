<x-admin.layout :title="__('Manage Colleges')" :heading="__('Colleges')" :subheading="__('Create, review, and maintain the colleges used by APAS initialization.')">
    <div class="flex items-center justify-end">
        <flux:button :href="route('admin.colleges.create')" variant="primary" wire:navigate>
            {{ __('Add College') }}
        </flux:button>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-950/60">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Abbreviation') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse ($colleges as $college)
                    <tr>
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $college->name }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $college->abbreviation ?: '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <flux:button size="sm" variant="ghost" :href="route('admin.colleges.show', $college)" wire:navigate>
                                    {{ __('View') }}
                                </flux:button>

                                <flux:button size="sm" variant="subtle" :href="route('admin.colleges.edit', $college)" wire:navigate>
                                    {{ __('Edit') }}
                                </flux:button>

                                <form method="POST" action="{{ route('admin.colleges.destroy', $college) }}">
                                    @csrf
                                    @method('DELETE')

                                    <flux:button size="sm" variant="danger" type="submit">
                                        {{ __('Delete') }}
                                    </flux:button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('No colleges have been created yet.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin.layout>