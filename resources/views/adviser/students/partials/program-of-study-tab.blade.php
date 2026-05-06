@php($formatUnits = fn (float $units): string => rtrim(rtrim(number_format($units, 2, '.', ''), '0'), '.'))
@php($formatGpa = fn (?float $gpa): string => $gpa === null ? 'N/A' : number_format($gpa, 5))
@php($availableCurrentTermSubjects = $currentTermEnrollment['subjects']->reject(fn ($subject) => $currentTermEnrollment['searchSubjects']->contains(fn ($searchSubject) => (int) $searchSubject->id === (int) $subject->id))->values())
@php($currentTermSearchSubjectCatalog = $currentTermEnrollment['searchSubjects']->map(fn ($subject) => [
    'id' => $subject->id,
    'code' => $subject->subject_code,
    'title' => $subject->subject_title,
    'units' => $formatUnits((float) $subject->credit_units),
    'gradingSystem' => ucfirst($subject->grading_system),
])->values())

<section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
    <div class="flex items-start justify-between gap-6 max-lg:flex-col max-lg:items-start">
        <div class="space-y-2">
            <p class="text-sm font-medium uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">{{ __('Current Academic Term') }}</p>
            <h2 class="text-xl font-semibold text-zinc-950 dark:text-zinc-50">{{ __('Add Actual Enrolled Subjects') }}</h2>
            @if (($currentTermEnrollment['activeAcademicTerm'] ?? null) !== null)
                <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('Use the active term to record the subjects this advisee is actually taking right now.') }}</p>
                <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $currentTermEnrollment['activeAcademicTerm']->fullLabel() }} · {{ __('School Year :schoolYear', ['schoolYear' => $currentTermEnrollment['schoolYear']]) }}</p>
            @else
                <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('Set an active academic term before adding current-term enrolled subjects.') }}</p>
            @endif
        </div>

        <form
            method="POST"
            action="{{ route('adviser.students.current-term-enrollments.store', $studentProfile) }}"
            class="w-full max-w-xl space-y-3"
            x-data="{
                query: '',
                selectedId: @js(old('subject_id') !== null ? (int) old('subject_id') : null),
                subjects: @js($currentTermSearchSubjectCatalog),
                get filteredSubjects() {
                    if (this.query.trim().length < 2) {
                        return [];
                    }

                    const search = this.query.trim().toLowerCase();

                    return this.subjects
                        .filter((subject) => {
                            const haystack = [subject.code, subject.title, subject.units, subject.gradingSystem].join(' ').toLowerCase();

                            return haystack.includes(search);
                        })
                        .slice(0, 25);
                },
                get selectedSubject() {
                    return this.subjects.find((subject) => subject.id === this.selectedId) ?? null;
                },
                hasSubject(subjectId) {
                    return this.selectedId === subjectId;
                },
                addSubject(subjectId) {
                    this.selectedId = subjectId;
                    this.query = '';
                },
                removeSubject() {
                    this.selectedId = null;
                }
            }"
        >
            @csrf

            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-200" for="current_term_subject_search">{{ __('Subject') }}</label>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Regular current-term prospectus subjects are already listed below in the Program of Study. Use search here for actual subject choices that still need manual selection, such as elective matches.') }}</p>
                </div>

                @if ($currentTermEnrollment['searchSubjects']->isNotEmpty())
                    <div class="relative w-full space-y-3">
                        <label class="block">
                            <span class="sr-only">{{ __('Search subjects') }}</span>
                            <input
                                id="current_term_subject_search"
                                x-model="query"
                                type="search"
                                @disabled(($currentTermEnrollment['activeAcademicTerm'] ?? null) === null || $currentTermEnrollment['searchSubjects']->isEmpty())
                                class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 shadow-sm outline-none transition placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200 disabled:cursor-not-allowed disabled:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800 dark:disabled:bg-zinc-900"
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
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Type at least two characters to search across the remaining current-term subjects.') }}</p>

                            <div class="mt-3 max-h-80 space-y-3 overflow-y-auto pr-1">
                                <p x-show="filteredSubjects.length === 0" class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No subjects match your search.') }}</p>

                                <template x-for="subject in filteredSubjects" :key="`current-term-search-${subject.id}`">
                                    <div class="flex items-start justify-between gap-3 rounded-2xl border border-zinc-200 px-4 py-3 text-sm dark:border-zinc-700">
                                        <div>
                                            <p class="font-medium text-zinc-900 dark:text-zinc-100" x-text="`${subject.code} · ${subject.title}`"></p>
                                            <p class="mt-1 text-zinc-600 dark:text-zinc-300" x-text="`${subject.units} units · ${subject.gradingSystem}`"></p>
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
                @else
                    <div class="rounded-2xl border border-dashed border-zinc-300 px-4 py-3 text-sm text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
                        {{ __('No extra search-select subjects are available for this active term right now. Use the row action below to start a listed current-term prospectus subject.') }}
                    </div>
                @endif

                <input type="hidden" name="subject_id" :value="selectedId ?? ''">

                <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Selected Subject') }}</p>
                        <span class="text-sm text-zinc-500 dark:text-zinc-400" x-text="selectedSubject ? '{{ __('1 selected') }}' : '{{ __('None selected') }}'"></span>
                    </div>

                    <div class="mt-3 space-y-3">
                        <template x-if="selectedSubject">
                            <div class="flex items-start justify-between gap-3 rounded-2xl bg-zinc-50 px-4 py-3 text-sm dark:bg-zinc-950/60">
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-zinc-100" x-text="`${selectedSubject.code} · ${selectedSubject.title}`"></p>
                                    <p class="mt-1 text-zinc-600 dark:text-zinc-300" x-text="`${selectedSubject.units} units · ${selectedSubject.gradingSystem}`"></p>
                                </div>

                                <button type="button" class="text-sm font-medium text-rose-600 hover:text-rose-700 dark:text-rose-300" @click="removeSubject()">
                                    {{ __('Remove') }}
                                </button>
                            </div>
                        </template>

                        <p x-show="! selectedSubject" class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No current-term subject selected yet.') }}</p>
                    </div>
                </div>

                @if ($availableCurrentTermSubjects->isNotEmpty())
                    <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Available Listed Current-Term Subjects') }}</p>
                            <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('These are prospectus subjects for the active term that are not yet recorded on the student ledger. Start them here only when the student is actually enrolled.') }}</p>
                        </div>

                        <div class="mt-3 space-y-3">
                            @foreach ($availableCurrentTermSubjects as $subject)
                                <div class="flex items-start justify-between gap-3 rounded-2xl border border-zinc-200 px-4 py-3 text-sm dark:border-zinc-700">
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $subject->subject_code }} · {{ $subject->subject_title }}</p>
                                        <p class="mt-1 text-zinc-600 dark:text-zinc-300">{{ __(':units units · :gradingSystem', ['units' => $formatUnits((float) $subject->credit_units), 'gradingSystem' => ucfirst($subject->grading_system)]) }}</p>
                                    </div>

                                    <form method="POST" action="{{ route('adviser.students.current-term-enrollments.store', $studentProfile) }}">
                                        @csrf
                                        <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-zinc-200 px-3 py-2 text-xs font-semibold text-zinc-800 transition hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">
                                            {{ __('Start Current-Term Enrollment') }}
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            @error('current_term_subject_id')
                <p class="text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
            @enderror
            @error('subject_id')
                <p class="text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
            @enderror

            @if ($currentTermEnrollment['searchSubjects']->isNotEmpty())
                <button
                    type="submit"
                    @disabled(($currentTermEnrollment['activeAcademicTerm'] ?? null) === null || $currentTermEnrollment['searchSubjects']->isEmpty())
                    x-bind:disabled="selectedId === null"
                    class="inline-flex items-center justify-center rounded-xl bg-zinc-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-zinc-800 disabled:cursor-not-allowed disabled:bg-zinc-300 dark:bg-zinc-100 dark:text-zinc-950 dark:hover:bg-zinc-200 dark:disabled:bg-zinc-800 dark:disabled:text-zinc-400"
                >
                    {{ __('Add Current-Term Subject') }}
                </button>
            @endif
        </form>
    </div>
</section>

<section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Cumulative GPA: :gpa', ['gpa' => $formatGpa($programOfStudySummary['cumulativeGpa'] ?? null)]) }}</p>
</section>

@if ($programOfStudy->isEmpty())
    <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No enrolled or taken subjects are recorded on this student ledger yet.') }}</p>
    </section>
@else
    <div class="space-y-6">
        @foreach ($programOfStudy as $schoolYearGroup)
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="space-y-1">
                    <h2 class="text-xl font-semibold text-zinc-950 dark:text-zinc-50">{{ __('Academic Year :schoolYear', ['schoolYear' => $schoolYearGroup['schoolYear']]) }}</h2>
                </div>

                <div class="mt-6 space-y-6">
                    @foreach ($schoolYearGroup['terms'] as $termData)
                        <article>
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ $termData['term']->term_name }}</h3>
                                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Semester GPA: :gpa', ['gpa' => $formatGpa($termData['semesterGpa'] ?? null)]) }}</p>
                            </div>

                            <div class="mt-3 overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700">
                                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                                    <thead class="bg-zinc-50 dark:bg-zinc-900/60">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Subject Code') }}</th>
                                            <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Subject Title') }}</th>
                                            <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Units') }}</th>
                                            <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Pre-Requisite') }}</th>
                                            <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Co-Requisite') }}</th>
                                            <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">{{ __('Grade') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-transparent">
                                        @foreach ($termData['rows'] as $row)
                                            <tr class="{{ $row['completed'] ? 'bg-emerald-50/80 dark:bg-emerald-500/10' : ($row['failed'] ? 'bg-rose-50/80 dark:bg-rose-500/10' : '') }}">
                                                <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $row['code'] }}</td>
                                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                                    @if (($row['electiveLabel'] ?? null) !== null)
                                                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row['electiveLabel'] }}</p>
                                                        <p class="mt-1 text-xs uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-300">{{ $row['title'] }}</p>
                                                    @else
                                                        {{ $row['title'] }}
                                                    @endif
                                                    @if ($row['isRetaken'])
                                                        <p class="mt-2 text-xs font-semibold uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">{{ __('Retake #:attempt', ['attempt' => $row['attemptNumber'] ?? count($row['attemptHistory'])]) }}</p>
                                                        <div class="mt-1 space-y-1 text-xs text-zinc-500 dark:text-zinc-400">
                                                            @foreach ($row['attemptHistory'] as $attempt)
                                                                <p>{{ __('Attempt :attempt · :schoolYear · :term · :result', ['attempt' => $attempt['attemptNumber'], 'schoolYear' => $attempt['schoolYear'], 'term' => $attempt['termName'], 'result' => $attempt['result']]) }}</p>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $formatUnits($row['units']) }}</td>
                                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $row['prerequisites'] }}</td>
                                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $row['corequisites'] }}</td>
                                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                                    @if ($row['grade'] !== '')
                                                        {{ $row['grade'] }}
                                                        @if (($row['gradeContext'] ?? null) !== null)
                                                            <p class="mt-1 text-xs font-medium uppercase tracking-[0.16em] text-zinc-500 dark:text-zinc-400">{{ $row['gradeContext'] }}</p>
                                                        @endif
                                                    @elseif (($row['status'] ?? null) === 'in_progress' && ($row['enrollment']?->academic_term_id ?? null) !== null)
                                                        <form method="POST" action="{{ route('adviser.students.current-term-enrollments.submit-grade', ['studentProfile' => $studentProfile, 'studentSubjectEnrollment' => $row['enrollment']]) }}" class="space-y-2">
                                                            @csrf
                                                            <p class="text-xs font-medium uppercase tracking-[0.16em] text-sky-800 dark:text-sky-300">{{ __('In Progress') }}</p>
                                                            <input
                                                                type="text"
                                                                name="submitted_grade"
                                                                value="{{ old('submitted_grade') }}"
                                                                placeholder="{{ __('Enter grade') }}"
                                                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm outline-none transition focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800"
                                                            >
                                                            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-sky-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-sky-500">
                                                                {{ __('Submit Grade') }}
                                                            </button>
                                                        </form>
                                                    @elseif (($row['status'] ?? null) === 'in_progress')
                                                        <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-medium text-sky-800 dark:bg-sky-500/15 dark:text-sky-300">{{ __('In Progress') }}</span>
                                                    @elseif (($row['status'] ?? null) === 'grade_submitted')
                                                        <div class="space-y-2">
                                                            <p class="text-xs font-medium uppercase tracking-[0.16em] text-amber-700 dark:text-amber-300">{{ __('Submitted: :grade', ['grade' => $row['submittedGrade'] ?: __('N/A')]) }}</p>
                                                            @if (($row['enrollment']?->academic_term_id ?? null) !== null)
                                                                <form method="POST" action="{{ route('adviser.students.current-term-enrollments.submit-grade', ['studentProfile' => $studentProfile, 'studentSubjectEnrollment' => $row['enrollment']]) }}" class="space-y-2">
                                                                    @csrf
                                                                    <input
                                                                        type="text"
                                                                        name="submitted_grade"
                                                                        value="{{ old('submitted_grade', $row['submittedGrade']) }}"
                                                                        placeholder="{{ __('Revise submitted grade') }}"
                                                                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm outline-none transition focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800"
                                                                    >
                                                                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-zinc-200 px-3 py-2 text-xs font-semibold text-zinc-800 transition hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">
                                                                        {{ __('Revise Submitted Grade') }}
                                                                    </button>
                                                                </form>
                                                                <form method="POST" action="{{ route('adviser.students.current-term-enrollments.confirm-grade', ['studentProfile' => $studentProfile, 'studentSubjectEnrollment' => $row['enrollment']]) }}">
                                                                    @csrf
                                                                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-amber-500 px-3 py-2 text-xs font-semibold text-zinc-950 transition hover:bg-amber-400">
                                                                        {{ __('Confirm Grade') }}
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
@endif