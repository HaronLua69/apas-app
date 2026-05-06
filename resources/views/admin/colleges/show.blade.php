<x-admin.layout :title="$college->name" :heading="$college->name" :subheading="__('Manage departments under this college.')" :backHref="route('admin.colleges.index')" :backLabel="__('Back to Colleges')">
    <div class="flex items-center justify-between gap-3 max-md:flex-col max-md:items-start">
        <div class="text-sm text-zinc-600 dark:text-zinc-300">
            <p><span class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Dean') }}:</span> {{ $college->dean }}</p>
            <p><span class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Abbreviation') }}:</span> {{ $college->abbreviation ?: '—' }}</p>
        </div>

        <flux:button :href="route('admin.colleges.departments.create', $college)" variant="primary" wire:navigate>
            {{ __('Add Department') }}
        </flux:button>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-950/60">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Department') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Programs') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Subjects') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse ($college->departments as $department)
                    <tr>
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $department->name }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $department->courses()->count() }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $department->subjects()->count() }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <flux:button size="sm" variant="ghost" :href="route('admin.departments.show', $department)" wire:navigate>
                                    {{ __('View') }}
                                </flux:button>
                                <flux:button size="sm" variant="subtle" :href="route('admin.departments.edit', $department)" wire:navigate>
                                    {{ __('Edit') }}
                                </flux:button>
                                <form method="POST" action="{{ route('admin.departments.destroy', $department) }}">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button size="sm" variant="danger" type="submit">{{ __('Delete') }}</flux:button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">{{ __('No departments have been created for this college yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin.layout>