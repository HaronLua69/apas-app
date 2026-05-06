<x-admin.layout :title="__('Academic Terms')" :heading="__('Academic Terms')" :subheading="__('Define the academic year and term ranges so APAS can resolve the current term automatically from the calendar date.')">
    <div class="grid gap-4 lg:grid-cols-[minmax(0,0.72fr)_minmax(0,1.28fr)]">
        <section class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Current Term') }}</p>
                    <h2 class="mt-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Active Term') }}</h2>
                </div>

                <flux:button :href="route('admin.academic-terms.create')" variant="primary" wire:navigate>
                    {{ __('Add Academic Term') }}
                </flux:button>
            </div>

            @if ($activeAcademicTerm)
                <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/40">
                    <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">{{ $activeAcademicTerm->fullLabel() }}</p>
                    <p class="mt-1 text-sm text-emerald-700/80 dark:text-emerald-300/80">{{ $activeAcademicTerm->date_from->format('M d, Y') }} - {{ $activeAcademicTerm->date_to->format('M d, Y') }}</p>
                </div>
            @else
                <div class="mt-4 rounded-2xl border border-dashed border-zinc-300 p-4 text-sm text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
                    {{ __('No active academic term matches today. Add a date range that covers the current date if you want APAS to resolve one automatically.') }}
                </div>
            @endif
        </section>

        <section class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-950/60">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Academic Year') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Term') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Date From') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Date To') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @forelse ($academicTerms as $academicTerm)
                        <tr>
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $academicTerm->academicYearLabel() }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $academicTerm->term_name }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $academicTerm->date_from->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $academicTerm->date_to->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                                @if ($activeAcademicTerm?->is($academicTerm))
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">{{ __('Active') }}</span>
                                @else
                                    <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ __('Inactive') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button size="sm" variant="subtle" :href="route('admin.academic-terms.edit', $academicTerm)" wire:navigate>
                                        {{ __('Edit') }}
                                    </flux:button>

                                    <form method="POST" action="{{ route('admin.academic-terms.destroy', $academicTerm) }}">
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
                                {{ __('No academic terms have been created yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
</x-admin.layout>