@php($termOptions = ['1st Semester', '2nd Semester', 'Summer Term'])
@php($selectedSubjectIds = collect(old('subject_ids', isset($prospectusTerm) ? $prospectusTerm->subjects->pluck('id')->all() : []))->map(fn ($id) => (int) $id)->values())
@php($selectedElectiveNames = collect(old('elective_names', isset($prospectusTerm) ? $prospectusTerm->electives->pluck('name')->all() : []))->map(fn ($name) => trim((string) $name))->values())
@php($selectedElectiveCategories = collect(old('elective_categories', isset($prospectusTerm) ? $prospectusTerm->electives->map(fn ($elective) => $elective->category?->value)->all() : []))->values())
@php($selectedElectives = $selectedElectiveNames->map(function ($name, $index) use ($selectedElectiveCategories) {
    $trimmedName = trim((string) $name);

    if ($trimmedName === '') {
        return null;
    }

    return [
        'id' => 'existing-'.$index,
        'name' => $trimmedName,
        'category' => $selectedElectiveCategories->get($index),
    ];
})->filter()->values())
@php($subjectCatalog = $availableSubjects->map(fn ($subject) => [
    'id' => $subject->id,
    'code' => $subject->subject_code,
    'title' => $subject->subject_title,
    'units' => number_format((float) $subject->credit_units, 2),
    'gradingSystem' => ucfirst($subject->grading_system),
    'department' => $subject->department->abbreviation ?: $subject->department->name,
    'college' => $subject->department->college->abbreviation ?: $subject->department->college->name,
])->values())

<div
    class="space-y-6"
    x-data="{
        query: '',
        isElectiveModalOpen: false,
        electiveName: '',
        electiveCategory: '',
        electiveCategories: @js($electiveCategoryOptions),
        selectedIds: @js($selectedSubjectIds),
        selectedElectives: @js($selectedElectives->all()),
        subjects: @js($subjectCatalog),
        categoryLabel(category) {
            return this.electiveCategories.find((option) => option.value === category)?.label ?? category;
        },
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
        addElective() {
            const name = this.electiveName.trim();
            const category = this.electiveCategory;

            if (name.length === 0 || category.length === 0) {
                return;
            }

            this.selectedElectives.push({
                id: `elective-${Date.now()}-${this.selectedElectives.length + 1}`,
                name,
                category,
            });

            this.electiveName = '';
            this.electiveCategory = '';
            this.isElectiveModalOpen = false;
        },
        openElectiveModal() {
            this.isElectiveModalOpen = true;

            this.$nextTick(() => this.$refs.electiveNameInput?.focus());
        },
        closeElectiveModal() {
            this.isElectiveModalOpen = false;
            this.electiveName = '';
            this.electiveCategory = '';
        },
        removeSubject(subjectId) {
            this.selectedIds = this.selectedIds.filter((id) => id !== subjectId);
        },
        removeElective(electiveId) {
            this.selectedElectives = this.selectedElectives.filter((elective) => elective.id !== electiveId);
        },
        hasSubject(subjectId) {
            return this.selectedIds.includes(subjectId);
        }
    }"
>
    <div class="grid gap-6 md:grid-cols-2">
        <flux:select name="year_level" :label="__('Year Level')" required>
            @for ($yearLevel = 1; $yearLevel <= 4; $yearLevel++)
                <option value="{{ $yearLevel }}" @selected((string) old('year_level', $prospectusTerm->year_level ?? 1) === (string) $yearLevel)>
                    {{ __('Year') }} {{ $yearLevel }}
                </option>
            @endfor
        </flux:select>

        <flux:select name="term_name" :label="__('Term')" required>
            @foreach ($termOptions as $termOption)
                <option value="{{ $termOption }}" @selected(old('term_name', $prospectusTerm->term_name ?? '1st Semester') === $termOption)>
                    {{ __($termOption) }}
                </option>
            @endforeach
        </flux:select>
    </div>

    <div>
        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_24rem] lg:items-start">
            <div>
                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Semestral Distribution of Subjects') }}</p>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Search all subjects by code or title, then add them to this year-level and term distribution.') }}</p>
            </div>

            <div class="relative w-full space-y-3">
                <div class="flex items-start gap-3 max-sm:flex-col">
                    <label class="block min-w-0 flex-1">
                        <span class="sr-only">{{ __('Search subjects') }}</span>
                        <input
                            x-model="query"
                            type="search"
                            class="w-full rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm outline-none ring-0 transition placeholder:text-zinc-400 focus:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                            placeholder="{{ __('Search by subject code or subject title') }}"
                        >
                    </label>

                    <button
                        type="button"
                        class="shrink-0 rounded-xl border border-zinc-200 px-3 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-900"
                        @click="openElectiveModal()"
                    >
                        {{ __('Add Elective') }}
                    </button>
                </div>

                <div
                    x-cloak
                    x-show="query.trim().length >= 2"
                    x-transition.origin.top
                    class="absolute inset-x-0 top-full z-20 mt-2 rounded-2xl border border-zinc-200 bg-white p-4 shadow-xl dark:border-zinc-700 dark:bg-zinc-950"
                >
                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Search Results') }}</p>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Type at least two characters to search across all subjects.') }}</p>

                    <div class="mt-3 max-h-80 space-y-3 overflow-y-auto pr-1">
                        <p x-show="filteredSubjects.length === 0" class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No subjects match your search.') }}</p>

                        <template x-for="subject in filteredSubjects" :key="`search-${subject.id}`">
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

        <template x-for="subjectId in selectedIds" :key="`selected-hidden-${subjectId}`">
            <input type="hidden" name="subject_ids[]" :value="subjectId">
        </template>

        <template x-for="(elective, index) in selectedElectives" :key="`selected-elective-hidden-${elective.id}`">
            <div>
                <input type="hidden" :name="`elective_names[${index}]`" :value="elective.name">
                <input type="hidden" :name="`elective_categories[${index}]`" :value="elective.category">
            </div>
        </template>

        <div class="mt-4 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Selected Prospectus Entries') }}</p>
                <span class="text-sm text-zinc-500 dark:text-zinc-400" x-text="`${selectedIds.length + selectedElectives.length} selected`"></span>
            </div>

            <div class="mt-3 space-y-3">
                <template x-for="(elective, index) in selectedElectives" :key="`selected-elective-${elective.id}`">
                    <div class="flex items-start justify-between gap-3 rounded-2xl bg-zinc-50 px-4 py-3 text-sm dark:bg-zinc-950/60">
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-zinc-100" x-text="`Elective ${index + 1}`"></p>
                            <p class="mt-1 text-zinc-600 dark:text-zinc-300" x-text="elective.name"></p>
                            <p class="mt-1 text-xs uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400" x-text="categoryLabel(elective.category)"></p>
                        </div>

                        <button type="button" class="text-sm font-medium text-rose-600 hover:text-rose-700 dark:text-rose-300" @click="removeElective(elective.id)">
                            {{ __('Remove') }}
                        </button>
                    </div>
                </template>

                <template x-for="subject in selectedSubjects" :key="`selected-${subject.id}`">
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

                <p x-show="selectedIds.length === 0 && selectedElectives.length === 0" class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No subjects or electives selected yet.') }}</p>
            </div>
        </div>

        @error('subject_ids')
            <p class="text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
        @enderror

        @error('elective_names')
            <p class="text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
        @enderror

        @error('elective_categories')
            <p class="text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
        @enderror

    </div>

    <div
        x-cloak
        x-show="isElectiveModalOpen"
        x-transition.opacity
        class="fixed inset-0 z-40 flex items-center justify-center bg-zinc-950/40 px-4 py-6"
        @click.self="closeElectiveModal()"
        @keydown.escape.window="closeElectiveModal()"
    >
        <div class="w-full max-w-lg rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Add Elective') }}</h3>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Enter the elective label that should appear in the prospectus, such as Technical Elective, Cognate Course, or Foreign Language.') }}</p>
                </div>

                <button type="button" class="text-sm font-medium text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200" @click="closeElectiveModal()">
                    {{ __('Close') }}
                </button>
            </div>

            <div class="mt-5 space-y-4">
                <div>
                    <label class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Elective shown in the prospectus') }}</label>
                    <input
                        x-ref="electiveNameInput"
                        x-model="electiveName"
                        type="text"
                        class="mt-2 w-full rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm outline-none ring-0 transition placeholder:text-zinc-400 focus:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                        placeholder="{{ __('Technical Elective') }}"
                        @keydown.enter.prevent="addElective()"
                    >
                </div>

                <div>
                    <label class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Elective category') }}</label>
                    <select
                        x-model="electiveCategory"
                        class="mt-2 w-full rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm outline-none ring-0 transition focus:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                    >
                        <option value="">{{ __('Select an elective category') }}</option>
                        @foreach ($electiveCategoryOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="rounded-2xl bg-zinc-50 p-4 text-sm text-zinc-600 dark:bg-zinc-950/60 dark:text-zinc-300">
                    <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('How it will appear') }}</p>
                    <p class="mt-2" x-text="electiveName.trim().length > 0 ? `Elective ${selectedElectives.length + 1} - ${electiveName.trim()}` : '{{ __('Elective X - Technical Elective') }}'"></p>
                    <p class="mt-2 text-zinc-500 dark:text-zinc-400" x-text="electiveCategory ? categoryLabel(electiveCategory) : '{{ __('Choose a category to control which real subjects can satisfy this elective.') }}'"></p>
                    <p class="mt-2 text-zinc-500 dark:text-zinc-400">{{ __('Units, Pre-Requisite, and Co-Requisite will display as "As the course requires" because the actual elective subject depends on the student record.') }}</p>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <button
                    type="button"
                    class="rounded-xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-900"
                    @click="closeElectiveModal()"
                >
                    {{ __('Cancel') }}
                </button>

                <button
                    type="button"
                    class="rounded-xl bg-zinc-900 px-3 py-2 text-sm font-medium text-white transition hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
                    @click="addElective()"
                >
                    {{ __('Confirm Elective') }}
                </button>
            </div>
        </div>
    </div>
</div>