@php($prerequisites = $subject->requisites->where('type', 'prerequisite'))
@php($corequisites = $subject->requisites->where('type', 'corequisite'))
@php($selectedElectiveScopeCategories = collect(old('elective_scope_categories', $subject->electiveScopes->mapWithKeys(fn ($scope) => [$scope->course_id.':'.($scope->major_id ?? 'all') => $scope->category?->value])->all())))
@php($isElective = old('is_elective', $subject->electiveScopes->isNotEmpty()))

<x-admin.layout :title="$subject->subject_code.' · '.$subject->subject_title" :heading="$subject->subject_code.' · '.$subject->subject_title" :subheading="__('Review the subject details and manage actions from this page.')" :backHref="route('admin.departments.show', ['department' => $subject->department, 'section' => 'subjects'])" :backLabel="__('Back to Department')">
    <div class="space-y-4">
        <section class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
            <div class="flex items-start justify-between gap-3 max-md:flex-col max-md:items-start">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Subject Details') }}</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Description and academic metadata for this subject.') }}</p>
                </div>

                <div class="flex items-center gap-2 max-sm:flex-wrap">
                    <flux:button size="sm" variant="ghost" :href="route('admin.subjects.requisites.index', $subject)" wire:navigate>{{ __('Manage Requisites') }}</flux:button>
                    <flux:button size="sm" variant="ghost" :href="route('admin.subjects.edit', $subject)" wire:navigate>{{ __('Edit') }}</flux:button>
                    <form method="POST" action="{{ route('admin.subjects.destroy', $subject) }}">
                        @csrf
                        @method('DELETE')
                        <flux:button size="sm" variant="danger" type="submit">{{ __('Delete') }}</flux:button>
                    </form>
                </div>
            </div>

            <dl class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-950/60">
                    <dt class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Units') }}</dt>
                    <dd class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ rtrim(rtrim(number_format((float) $subject->credit_units, 2, '.', ''), '0'), '.') }}</dd>
                </div>
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-950/60">
                    <dt class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Grading System') }}</dt>
                    <dd class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ ucfirst($subject->grading_system) }}</dd>
                </div>
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-950/60">
                    <dt class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Subject Types') }}</dt>
                    <dd class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ implode(', ', $subject->subject_types) }}</dd>
                </div>
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-950/60">
                    <dt class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Counts Toward GPA') }}</dt>
                    <dd class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $subject->counts_toward_gpa ? __('Yes') : __('No') }}</dd>
                </div>
            </dl>

            <div class="mt-4 rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-950/60">
                <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Description') }}</p>
                <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-200">{{ $subject->description ?: __('No description provided.') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700" x-data="{ isElective: @js((bool) $isElective) }">
            <div class="flex items-start justify-between gap-3 max-md:flex-col max-md:items-start">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Elective Scope') }}</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Mark this subject as an elective option for specific programs or majors so passed grades can satisfy prospectus elective placeholders.') }}</p>
                </div>

                @if ($subject->electiveScopes->isNotEmpty())
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium uppercase tracking-[0.2em] text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">{{ __('Configured') }}</span>
                @endif
            </div>

            <form method="POST" action="{{ route('admin.subjects.elective-scopes.update', $subject) }}" class="mt-4 space-y-4">
                @csrf
                @method('PUT')

                <flux:checkbox name="is_elective" :label="__('Set this subject as an elective option')" :checked="$isElective" x-model="isElective" />

                <div class="space-y-4" x-show="isElective" x-cloak>
                    @forelse ($subject->department->courses as $course)
                        <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="flex items-start justify-between gap-3 max-md:flex-col max-md:items-start">
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $course->abbreviation ?: $course->name }}</p>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $course->name }}</p>
                                </div>
                            </div>

                            <flux:select :name="'elective_scope_categories['.$course->id.':all]'" :label="__('Elective category for all majors in this program')" x-bind:disabled="!isElective">
                                <option value="">{{ __('Not an elective for this scope') }}</option>
                                @foreach ($electiveCategoryOptions as $option)
                                    <option value="{{ $option['value'] }}" @selected(($selectedElectiveScopeCategories->get($course->id.':all')) === $option['value'])>{{ $option['label'] }}</option>
                                @endforeach
                            </flux:select>

                            @if ($course->majors->isNotEmpty())
                                <div class="mt-4 grid gap-3 md:grid-cols-2">
                                    @foreach ($course->majors as $major)
                                        <div class="rounded-2xl bg-zinc-50 p-3 dark:bg-zinc-950/60">
                                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $major->name }}</p>
                                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Major-specific elective scope') }}</p>

                                            <div class="mt-3">
                                                <flux:select :name="'elective_scope_categories['.$course->id.':'.$major->id.']'" :label="__('Elective category')" x-bind:disabled="!isElective">
                                                    <option value="">{{ __('Not an elective for this scope') }}</option>
                                                    @foreach ($electiveCategoryOptions as $option)
                                                        <option value="{{ $option['value'] }}" @selected(($selectedElectiveScopeCategories->get($course->id.':'.$major->id)) === $option['value'])>{{ $option['label'] }}</option>
                                                    @endforeach
                                                </flux:select>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Create a program under this department first before assigning elective scopes.') }}</p>
                    @endforelse
                </div>

                @if ($subject->electiveScopes->isNotEmpty())
                    <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-950/60">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Current elective bindings') }}</p>
                        <div class="mt-3 space-y-2">
                            @foreach ($subject->electiveScopes as $scope)
                                <p class="text-sm text-zinc-900 dark:text-zinc-100">
                                    <span class="font-medium">{{ $scope->categoryLabel() }}</span>
                                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('for') }}</span>
                                    {{ $scope->course->name }}
                                    @if ($scope->major)
                                        {{ __('- Major in') }} {{ $scope->major->name }}
                                    @else
                                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('(All majors)') }}</span>
                                    @endif
                                </p>
                            @endforeach
                        </div>
                    </div>
                @endif

                @error('elective_scope_categories')
                    <p class="text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
                @enderror

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">{{ __('Save Elective Settings') }}</flux:button>
                </div>
            </form>
        </section>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Pre-Requisite') }}</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($prerequisites as $requisite)
                        <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $requisite->requisiteSubject->subject_code }} · {{ $requisite->requisiteSubject->subject_title }}</p>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $requisite->requisiteSubject->department->college->abbreviation ?: $requisite->requisiteSubject->department->college->name }} / {{ $requisite->requisiteSubject->department->abbreviation ?: $requisite->requisiteSubject->department->name }}</p>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Applies to: :scope', ['scope' => $requisite->scopeLabel()]) }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No prerequisites have been assigned yet.') }}</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Co-Requisite') }}</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($corequisites as $requisite)
                        <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $requisite->requisiteSubject->subject_code }} · {{ $requisite->requisiteSubject->subject_title }}</p>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $requisite->requisiteSubject->department->college->abbreviation ?: $requisite->requisiteSubject->department->college->name }} / {{ $requisite->requisiteSubject->department->abbreviation ?: $requisite->requisiteSubject->department->name }}</p>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Applies to: :scope', ['scope' => $requisite->scopeLabel()]) }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No co-requisites have been assigned yet.') }}</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-admin.layout>