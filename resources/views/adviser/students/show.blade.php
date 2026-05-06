<x-layouts::app :title="$studentProfile->user->fullName()">
    @php($tabs = ['program-of-study' => __('Program of Study'), 'evaluation' => __('Evaluation'), 'subject-progression' => __('Subject Progression')])
    @php($formatUnits = fn (float $units): string => rtrim(rtrim(number_format($units, 2, '.', ''), '0'), '.'))

    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl p-6">
        <section class="rounded-3xl border border-zinc-200 bg-zinc-50 px-6 py-8 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-4 max-md:flex-col max-md:items-start">
                <div>
                    <p class="text-sm font-medium uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">{{ __('Advising View') }}</p>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <h1 class="text-3xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">{{ $studentProfile->user->fullName() }}</h1>
                        @if ($studentProfile->is_graduating)
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-amber-800 dark:bg-amber-500/15 dark:text-amber-300">{{ __('Graduating') }}</span>
                        @endif
                    </div>
                    <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('Review the same academic tabs this student sees in their own view.') }}</p>
                </div>

                <flux:button :href="route('adviser.students.index')" variant="ghost" wire:navigate>
                    {{ __('Back to Advisees') }}
                </flux:button>
            </div>
        </section>

        @if (session('status'))
            <section class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
                {{ session('status') }}
            </section>
        @endif

        @error('graduation')
            <section class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200">
                {{ $message }}
            </section>
        @enderror

        @error('grade_workflow')
            <section class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200">
                {{ $message }}
            </section>
        @enderror

        @error('submitted_grade')
            <section class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200">
                {{ $message }}
            </section>
        @enderror

        <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-6 max-lg:flex-col max-lg:items-start">
                <div class="space-y-2">
                    <p class="text-sm font-medium uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">{{ __('Graduation Recommendation') }}</p>
                    <h2 class="text-xl font-semibold text-zinc-950 dark:text-zinc-50">{{ __('Mark This Advisee as Graduating') }}</h2>
                    <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ __('This action is only available from the viewed student screen and can only be completed once.') }}</p>
                    <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Progress: :earned of :total units (:percentage)', ['earned' => $formatUnits($graduationEligibility['earnedUnits']), 'total' => $formatUnits($graduationEligibility['prospectusUnits']), 'percentage' => $graduationEligibility['earnedUnitPercentage'] !== null ? number_format($graduationEligibility['earnedUnitPercentage'], 1).'%' : __('N/A')]) }}</p>
                </div>

                <form method="POST" action="{{ route('adviser.students.graduating.store', $studentProfile) }}" class="w-full max-w-sm space-y-3" onsubmit="return confirm('Mark this student as graduating? This action cannot be undone.');">
                    @csrf
                    <input type="hidden" name="tab" value="{{ $tab }}">

                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 text-sm dark:border-zinc-700 dark:bg-zinc-950/50">
                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Current Status') }}</p>
                        <p class="mt-1 text-zinc-600 dark:text-zinc-300">
                            @if ($graduationEligibility['alreadyGraduating'])
                                {{ __('Already marked as graduating') }}
                            @elseif ($graduationEligibility['eligible'])
                                {{ __('Eligible to be marked as graduating') }}
                            @else
                                {{ __('Not eligible yet') }}
                            @endif
                        </p>
                    </div>

                    <button
                        type="submit"
                        @disabled(! $graduationEligibility['canMarkGraduating'])
                        class="inline-flex w-full items-center justify-center rounded-xl px-4 py-3 text-sm font-semibold transition {{ $graduationEligibility['canMarkGraduating'] ? 'bg-amber-500 text-zinc-950 hover:bg-amber-400' : 'cursor-not-allowed bg-zinc-200 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400' }}"
                    >
                        @if ($graduationEligibility['alreadyGraduating'])
                            {{ __('Already Marked as Graduating') }}
                        @elseif ($graduationEligibility['canMarkGraduating'])
                            {{ __('Mark as Graduating') }}
                        @else
                            {{ __('Not Eligible Yet') }}
                        @endif
                    </button>
                </form>
            </div>

            <div class="mt-6 grid gap-3 md:grid-cols-2">
                @foreach ($graduationEligibility['requirements'] as $requirement)
                    <article class="rounded-2xl border px-4 py-4 {{ $requirement['met'] ? 'border-emerald-200 bg-emerald-50/70 dark:border-emerald-500/30 dark:bg-emerald-500/10' : 'border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-950/50' }}">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $requirement['label'] }}</h3>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.16em] {{ $requirement['met'] ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' }}">
                                {{ $requirement['met'] ? __('Met') : __('Pending') }}
                            </span>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $requirement['detail'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-wrap gap-2">
                @foreach ($tabs as $tabKey => $label)
                    <a
                        href="{{ route('adviser.students.show', ['studentProfile' => $studentProfile, 'tab' => $tabKey]) }}"
                        class="rounded-full px-4 py-2 text-sm font-medium {{ $tab === $tabKey ? 'bg-zinc-950 text-white dark:bg-zinc-100 dark:text-zinc-950' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </section>

        @if ($tab === 'evaluation')
            @include('adviser.students.partials.evaluation-tab')
        @elseif ($tab === 'subject-progression')
            @include('adviser.students.partials.subject-progression-tab')
        @else
            @include('adviser.students.partials.program-of-study-tab')
        @endif
    </div>
</x-layouts::app>