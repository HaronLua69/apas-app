@php($selectedRequisiteId = old('requisite_subject_id'))
@php($requisiteCatalog = $availableSubjects->map(fn ($availableSubject) => [
    'id' => $availableSubject->id,
    'code' => $availableSubject->subject_code,
    'title' => $availableSubject->subject_title,
    'department' => $availableSubject->department->abbreviation ?: $availableSubject->department->name,
    'college' => $availableSubject->department->college->abbreviation ?: $availableSubject->department->college->name,
])->values())

<x-admin.layout :title="__('Subject Requisites')" :heading="$subject->subject_code.' · '.$subject->subject_title" :subheading="__('Manage prerequisites and co-requisites for this subject.')" :backHref="route('admin.subjects.show', $subject)" :backLabel="__('Back to Subject')">
    <div class="grid gap-4 lg:grid-cols-[0.9fr_1.1fr]">
        <section class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Add Requisite') }}</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Link another subject as a prerequisite or co-requisite.') }}</p>

            <form
                method="POST"
                action="{{ route('admin.subjects.requisites.store', $subject) }}"
                class="mt-6 space-y-6"
                x-data="{
                    query: '',
                    subjects: @js($requisiteCatalog),
                    selectedId: {{ $selectedRequisiteId !== null ? (int) $selectedRequisiteId : 'null' }},
                    get filteredSubjects() {
                        if (this.query.trim().length < 2) {
                            return [];
                        }

                        const search = this.query.trim().toLowerCase();

                        return this.subjects
                            .filter((availableSubject) => {
                                const haystack = [availableSubject.code, availableSubject.title, availableSubject.department, availableSubject.college]
                                    .join(' ')
                                    .toLowerCase();

                                return haystack.includes(search);
                            })
                            .slice(0, 25);
                    },
                    get selectedSubject() {
                        return this.subjects.find((availableSubject) => availableSubject.id === this.selectedId) ?? null;
                    },
                    selectSubject(subject) {
                        this.selectedId = subject.id;
                        this.query = `${subject.code} ${subject.title}`;
                    },
                    clearSelection() {
                        this.selectedId = null;
                        this.query = '';
                    }
                }"
                x-init="if (selectedSubject) { query = `${selectedSubject.code} ${selectedSubject.title}`; }"
            >
                @csrf

                <div>
                    <label class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Requisite Subject') }}</label>

                    <div class="relative mt-2">
                        <input type="hidden" name="requisite_subject_id" :value="selectedId ?? ''">

                        <input
                            x-model="query"
                            type="search"
                            class="w-full rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm outline-none ring-0 transition placeholder:text-zinc-400 focus:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                            placeholder="{{ __('Search by subject code or subject title') }}"
                            autocomplete="off"
                            required
                        >

                        <div
                            x-cloak
                            x-show="query.trim().length >= 2"
                            x-transition.origin.top
                            class="absolute inset-x-0 top-full z-20 mt-2 rounded-2xl border border-zinc-200 bg-white p-4 shadow-xl dark:border-zinc-700 dark:bg-zinc-950"
                        >
                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Search Results') }}</p>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Type at least two characters to search across all available requisite subjects.') }}</p>

                            <div class="mt-3 max-h-80 space-y-3 overflow-y-auto pr-1">
                                <p x-show="filteredSubjects.length === 0" class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No subjects match your search.') }}</p>

                                <template x-for="availableSubject in filteredSubjects" :key="`requisite-${availableSubject.id}`">
                                    <div class="flex items-start justify-between gap-3 rounded-2xl border border-zinc-200 px-4 py-3 text-sm dark:border-zinc-700">
                                        <div>
                                            <p class="font-medium text-zinc-900 dark:text-zinc-100" x-text="`${availableSubject.code} · ${availableSubject.title}`"></p>
                                            <p class="mt-1 text-zinc-500 dark:text-zinc-400" x-text="`${availableSubject.college} / ${availableSubject.department}`"></p>
                                        </div>

                                        <button
                                            type="button"
                                            class="rounded-xl border border-zinc-200 px-3 py-2 font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-900"
                                            @click="selectSubject(availableSubject)"
                                        >
                                            {{ __('Select') }}
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700" x-show="selectedSubject">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-zinc-100" x-text="selectedSubject ? `${selectedSubject.code} · ${selectedSubject.title}` : ''"></p>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400" x-text="selectedSubject ? `${selectedSubject.college} / ${selectedSubject.department}` : ''"></p>
                            </div>

                            <button type="button" class="text-sm font-medium text-rose-600 hover:text-rose-700 dark:text-rose-300" @click="clearSelection()">
                                {{ __('Clear') }}
                            </button>
                        </div>
                    </div>

                    @error('requisite_subject_id')
                        <p class="mt-2 text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
                    @enderror
                </div>

                <flux:select name="type" :label="__('Requisite Type')" required>
                    <option value="prerequisite" @selected(old('type', 'prerequisite') === 'prerequisite')>{{ __('Prerequisite') }}</option>
                    <option value="corequisite" @selected(old('type') === 'corequisite')>{{ __('Co-requisite') }}</option>
                </flux:select>

                <flux:select name="scope_key" :label="__('Applies To')" required>
                    @foreach ($availableScopes as $scope)
                        <option value="{{ $scope['key'] }}" @selected(old('scope_key', 'all') === $scope['key'])>{{ $scope['label'] }}</option>
                    @endforeach
                </flux:select>

                <div class="flex items-center justify-end gap-3">
                    <flux:button :href="route('admin.departments.show', $subject->department)" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary">{{ __('Save Requisite') }}</flux:button>
                </div>
            </form>
        </section>

        <section class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Current Requisites') }}</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Review all prerequisite and co-requisite links for this subject.') }}</p>

            <div class="mt-4 space-y-3">
                @forelse ($subject->requisites as $requisite)
                    <article class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $requisite->requisiteSubject->subject_code }} · {{ $requisite->requisiteSubject->subject_title }}</p>
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ ucfirst($requisite->type) }} · {{ $requisite->requisiteSubject->department->college->abbreviation ?: $requisite->requisiteSubject->department->college->name }} / {{ $requisite->requisiteSubject->department->abbreviation ?: $requisite->requisiteSubject->department->name }}</p>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Applies to: :scope', ['scope' => $requisite->scopeLabel()]) }}</p>
                            </div>

                            <form method="POST" action="{{ route('admin.subject-requisites.destroy', $requisite) }}">
                                @csrf
                                @method('DELETE')
                                <flux:button size="sm" variant="danger" type="submit">{{ __('Remove') }}</flux:button>
                            </form>
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No requisites have been assigned to this subject yet.') }}</p>
                @endforelse
            </div>
        </section>
    </div>
</x-admin.layout>