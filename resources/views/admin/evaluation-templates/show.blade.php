<x-admin.layout :title="$evaluationTemplate->title" :heading="$evaluationTemplate->title" :subheading="__('Manage advising classifications and subjects for this evaluation template.')" :backHref="route('admin.departments.show', $evaluationTemplate->course->department)" :backLabel="__('Back to Department')">
    <div class="grid gap-4 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
        <section class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
            <div>
                <p class="text-sm uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ $evaluationTemplate->course->abbreviation ?: $evaluationTemplate->course->name }}</p>
                <h2 class="mt-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $evaluationTemplate->major?->name ?: __('General program template') }}</h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $evaluationTemplate->description ?: __('No description provided.') }}</p>
            </div>

            <dl class="mt-5 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                <div class="flex items-center justify-between gap-3 rounded-xl bg-zinc-50 px-3 py-2 dark:bg-zinc-950/60">
                    <dt>{{ __('College / Department') }}</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $evaluationTemplate->course->department->college->abbreviation ?: $evaluationTemplate->course->department->college->name }} / {{ $evaluationTemplate->course->department->abbreviation ?: $evaluationTemplate->course->department->name }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3 rounded-xl bg-zinc-50 px-3 py-2 dark:bg-zinc-950/60">
                    <dt>{{ __('Program') }}</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $evaluationTemplate->course->name }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3 rounded-xl bg-zinc-50 px-3 py-2 dark:bg-zinc-950/60">
                    <dt>{{ __('Classifications') }}</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $evaluationTemplate->classifications->count() }}</dd>
                </div>
            </dl>

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <flux:button :href="route('admin.evaluation-templates.edit', $evaluationTemplate)" variant="primary" wire:navigate>{{ __('Edit Template') }}</flux:button>
                <flux:button :href="route('admin.departments.show', $evaluationTemplate->course->department)" variant="ghost" wire:navigate>{{ __('Back to Department') }}</flux:button>
            </div>
        </section>

        <section class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Classifications') }}</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Group departmental subjects into advising categories.') }}</p>
                </div>

                <flux:button :href="route('admin.evaluation-templates.classifications.create', $evaluationTemplate)" size="sm" variant="primary" wire:navigate>
                    {{ __('Add Classification') }}
                </flux:button>
            </div>

            <div class="mt-4 space-y-4">
                @forelse ($evaluationTemplate->classifications as $classification)
                    <article class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Order') }} {{ $classification->display_order }}</p>
                                <h3 class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $classification->name }}</h3>
                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $classification->description ?: __('No description provided.') }}</p>
                            </div>

                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" :href="route('admin.classifications.edit', $classification)" wire:navigate>{{ __('Edit') }}</flux:button>
                                <form method="POST" action="{{ route('admin.classifications.destroy', $classification) }}">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button size="sm" variant="danger" type="submit">{{ __('Delete') }}</flux:button>
                                </form>
                            </div>
                        </div>

                        <div class="mt-4 space-y-2 rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-950/60">
                            @forelse ($classification->subjects as $subject)
                                <div class="flex items-center justify-between gap-3 rounded-xl bg-white px-3 py-2 text-sm dark:bg-zinc-900">
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $subject->subject_code }} · {{ $subject->subject_title }}</p>
                                        <p class="text-zinc-600 dark:text-zinc-300">{{ number_format((float) $subject->credit_units, 2) }} {{ __('units') }}</p>
                                    </div>
                                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('Order') }} {{ $subject->pivot->display_order }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No subjects assigned to this classification yet.') }}</p>
                            @endforelse
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No classifications have been added to this evaluation template yet.') }}</p>
                @endforelse
            </div>
        </section>
    </div>
</x-admin.layout>