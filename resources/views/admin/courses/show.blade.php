<x-admin.layout :title="$course->name" :heading="$course->abbreviation ?: $course->name" :subheading="__('Review this program, its majors, and related advising resources.')" :backHref="route('admin.departments.show', ['department' => $course->department, 'section' => 'programs'])" :backLabel="__('Back to Department')">
    <div class="space-y-4">
        <section class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
            <div class="flex items-start justify-between gap-3 max-md:flex-col max-md:items-start">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Program Details') }}</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $course->name }}</p>
                </div>

                <div class="flex items-center gap-2">
                    <flux:button size="sm" variant="ghost" :href="route('admin.courses.edit', $course)" wire:navigate>{{ __('Edit') }}</flux:button>
                    <form method="POST" action="{{ route('admin.courses.destroy', $course) }}">
                        @csrf
                        @method('DELETE')
                        <flux:button size="sm" variant="danger" type="submit">{{ __('Delete') }}</flux:button>
                    </form>
                </div>
            </div>

            <dl class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-950/60">
                    <dt class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Abbreviation') }}</dt>
                    <dd class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $course->abbreviation ?: '—' }}</dd>
                </div>
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-950/60">
                    <dt class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Department') }}</dt>
                    <dd class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $course->department->name }}</dd>
                </div>
            </dl>

            <div class="mt-4 rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-950/60">
                <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Description') }}</p>
                <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-200">{{ $course->description ?: __('No description provided.') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Majors') }}</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('List the majors under this program and manage them here.') }}</p>
                </div>

                <flux:button size="sm" variant="primary" :href="route('admin.courses.majors.create', $course)" wire:navigate>
                    {{ __('Add Major') }}
                </flux:button>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($course->majors as $major)
                    <article class="flex items-start justify-between gap-3 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $major->name }}</p>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $major->description ?: __('No description provided.') }}</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <flux:button size="sm" variant="ghost" :href="route('admin.majors.edit', $major)" wire:navigate>{{ __('Edit') }}</flux:button>
                            <form method="POST" action="{{ route('admin.majors.destroy', $major) }}">
                                @csrf
                                @method('DELETE')
                                <flux:button size="sm" variant="danger" type="submit">{{ __('Delete') }}</flux:button>
                            </form>
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No majors have been created for this program.') }}</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Prospectuses') }}</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Curriculum plans for this program and its majors.') }}</p>
                </div>

                <flux:button size="sm" variant="subtle" :href="route('admin.courses.prospectuses.create', $course)" wire:navigate>
                    {{ __('Add Prospectus') }}
                </flux:button>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($course->prospectuses as $prospectus)
                    <div class="flex items-start justify-between gap-3 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $prospectus->title }}</p>
                                @if ($prospectus->is_active)
                                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">{{ __('Active') }}</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $prospectus->major?->name ?: __('General program prospectus') }}</p>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $prospectus->description ?: __('No description provided.') }}</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <flux:button size="sm" variant="ghost" :href="route('admin.prospectuses.show', $prospectus)" wire:navigate>{{ __('View') }}</flux:button>
                            <flux:button size="sm" variant="ghost" :href="route('admin.prospectuses.duplicate', $prospectus)" wire:navigate>{{ __('Duplicate') }}</flux:button>
                            <flux:button size="sm" variant="ghost" :href="route('admin.prospectuses.edit', $prospectus)" wire:navigate>{{ __('Edit') }}</flux:button>
                            <form method="POST" action="{{ route('admin.prospectuses.destroy', $prospectus) }}">
                                @csrf
                                @method('DELETE')
                                <flux:button size="sm" variant="danger" type="submit">{{ __('Delete') }}</flux:button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No prospectuses have been created for this program yet.') }}</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Evaluation Templates') }}</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Advising classification groups for this program and its majors.') }}</p>
                </div>

                <flux:button size="sm" variant="subtle" :href="route('admin.courses.evaluation-templates.create', $course)" wire:navigate>
                    {{ __('Add Evaluation Template') }}
                </flux:button>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($course->evaluationTemplates as $evaluationTemplate)
                    <div class="flex items-start justify-between gap-3 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $evaluationTemplate->title }}</p>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $evaluationTemplate->major?->name ?: __('General program template') }}</p>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $evaluationTemplate->description ?: __('No description provided.') }}</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <flux:button size="sm" variant="ghost" :href="route('admin.evaluation-templates.show', $evaluationTemplate)" wire:navigate>{{ __('View') }}</flux:button>
                            <flux:button size="sm" variant="ghost" :href="route('admin.evaluation-templates.edit', $evaluationTemplate)" wire:navigate>{{ __('Edit') }}</flux:button>
                            <form method="POST" action="{{ route('admin.evaluation-templates.destroy', $evaluationTemplate) }}">
                                @csrf
                                @method('DELETE')
                                <flux:button size="sm" variant="danger" type="submit">{{ __('Delete') }}</flux:button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No evaluation templates have been created for this program yet.') }}</p>
                @endforelse
            </div>
        </section>
    </div>
</x-admin.layout>