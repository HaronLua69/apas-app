@php($selectedSubjectIds = collect(old('subject_ids', isset($classification) ? $classification->subjects->pluck('id')->all() : []))->map(fn ($id) => (int) $id)->values())
@php($subjectCatalog = $availableSubjects->map(fn ($subject) => [
    'id' => $subject->id,
    'code' => $subject->subject_code,
    'title' => $subject->subject_title,
    'units' => number_format((float) $subject->credit_units, 2),
    'gradingSystem' => ucfirst($subject->grading_system),
    'department' => $subject->department->abbreviation ?: $subject->department->name,
    'college' => $subject->department->college->abbreviation ?: $subject->department->college->name,
])->values())

<div class="space-y-6">
    <div class="grid gap-6 md:grid-cols-2">
        <flux:input name="name" :label="__('Classification Name')" :value="old('name', $classification->name ?? '')" required autofocus />
        <flux:input name="display_order" :label="__('Display Order')" :value="old('display_order', $classification->display_order ?? 1)" type="number" min="1" max="999" required />
    </div>

    <flux:textarea name="description" :label="__('Description')">{{ old('description', $classification->description ?? '') }}</flux:textarea>

    <div
        x-data="{
            query: '',
            selectedIds: @js($selectedSubjectIds),
            subjects: @js($subjectCatalog),
            get selectedSubjects() {
                return this.subjects.filter((subject) => this.selectedIds.includes(subject.id));
            },
            get filteredSubjects() {
                if (this.query.trim().length < 2) {
                    return [];
                }

                const search = this.query.trim().toLowerCase();

                return this.subjects
                    .filter((subject) => {
                        const haystack = [subject.code, subject.title, subject.department, subject.college].join(' ').toLowerCase();

                        return haystack.includes(search);
                    })
                    .slice(0, 25);
            },
            addSubject(subjectId) {
                if (! this.selectedIds.includes(subjectId)) {
                    this.selectedIds.push(subjectId);
                }
            },
            removeSubject(subjectId) {
                this.selectedIds = this.selectedIds.filter((id) => id !== subjectId);
            },
            hasSubject(subjectId) {
                return this.selectedIds.includes(subjectId);
            }
        }"
    >
        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_24rem] lg:items-start">
            <div>
                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Assigned Subjects') }}</p>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Search all subjects by code or title, then add them to this classification. Selection order is preserved as display order.') }}</p>
            </div>

            <div class="relative w-full">
                <label class="block min-w-0">
                    <span class="sr-only">{{ __('Search subjects') }}</span>
                    <input
                        x-model="query"
                        type="search"
                        class="w-full rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm outline-none ring-0 transition placeholder:text-zinc-400 focus:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                        placeholder="{{ __('Search by subject code or subject title') }}"
                    >
                </label>

                <div
                    x-cloak
                    x-show="query.trim().length >= 2"
                    x-transition.origin.top
                    class="absolute inset-x-0 top-full z-20 mt-2 rounded-2xl border border-zinc-200 bg-white p-4 shadow-xl dark:border-zinc-700 dark:bg-zinc-950"
                >
                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Search Results') }}</p>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Type at least two characters to search across all subjects, including those offered by other colleges and departments.') }}</p>

                    <div class="mt-3 max-h-80 space-y-3 overflow-y-auto pr-1">
                        <p x-show="filteredSubjects.length === 0" class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No subjects match your search.') }}</p>

                        <template x-for="subject in filteredSubjects" :key="`classification-search-${subject.id}`">
                            <div class="flex items-start justify-between gap-3 rounded-2xl border border-zinc-200 px-4 py-3 text-sm dark:border-zinc-700">
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-zinc-100" x-text="`${subject.code} · ${subject.title}`"></p>
                                    <p class="mt-1 text-zinc-600 dark:text-zinc-300" x-text="`${subject.units} units · ${subject.gradingSystem}`"></p>
                                    <p class="mt-1 text-zinc-500 dark:text-zinc-400" x-text="`${subject.college} / ${subject.department}`"></p>
                                </div>

                                <button
                                    type="button"
                                    class="rounded-xl border border-zinc-200 px-3 py-2 font-medium text-zinc-700 transition hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-900"
                                    @click="addSubject(subject.id)"
                                    :disabled="hasSubject(subject.id)"
                                    x-text="hasSubject(subject.id) ? '{{ __('Added') }}' : '{{ __('Add Subject') }}'"
                                ></button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <template x-for="subjectId in selectedIds" :key="`classification-selected-hidden-${subjectId}`">
            <input type="hidden" name="subject_ids[]" :value="subjectId">
        </template>

        <div class="mt-4 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Selected Subjects') }}</p>
                <span class="text-sm text-zinc-500 dark:text-zinc-400" x-text="`${selectedIds.length} selected`"></span>
            </div>

            <div class="mt-3 space-y-3">
                <template x-for="subject in selectedSubjects" :key="`classification-selected-${subject.id}`">
                    <div class="flex items-start justify-between gap-3 rounded-2xl bg-zinc-50 px-4 py-3 text-sm dark:bg-zinc-950/60">
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-zinc-100" x-text="`${subject.code} · ${subject.title}`"></p>
                            <p class="mt-1 text-zinc-600 dark:text-zinc-300" x-text="`${subject.units} units · ${subject.gradingSystem}`"></p>
                            <p class="mt-1 text-zinc-500 dark:text-zinc-400" x-text="`${subject.college} / ${subject.department}`"></p>
                        </div>

                        <button type="button" class="text-sm font-medium text-rose-600 hover:text-rose-700 dark:text-rose-300" @click="removeSubject(subject.id)">
                            {{ __('Remove') }}
                        </button>
                    </div>
                </template>

                <p x-show="selectedIds.length === 0" class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No subjects selected yet.') }}</p>
            </div>
        </div>

        @error('subject_ids')
            <p class="mt-2 text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
        @enderror

        @error('subject_ids.*')
            <p class="mt-2 text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
        @enderror
    </div>
</div>