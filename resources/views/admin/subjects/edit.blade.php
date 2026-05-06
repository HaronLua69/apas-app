@php($subjectTypes = ['Lecture', 'Laboratory', 'Field', 'Seminar'])
@php($selectedSubjectTypes = collect(old('subject_types', $subject->subject_types))->values())

<x-admin.layout :title="__('Edit Subject')" :heading="__('Edit Subject')" :subheading="__('Update the details for this subject.')" :backHref="route('admin.subjects.show', $subject)" :backLabel="__('Back to Subject')">
    <div class="mb-6 flex justify-end">
        <flux:button :href="route('admin.subjects.requisites.index', $subject)" variant="ghost" wire:navigate>
            {{ __('Manage Requisites') }}
        </flux:button>
    </div>

    <form method="POST" action="{{ route('admin.subjects.update', $subject) }}" class="space-y-6">
        @csrf
        @method('PUT')
        <flux:input name="subject_code" :label="__('Subject Code')" :value="old('subject_code', $subject->subject_code)" required autofocus />
        <flux:input name="subject_title" :label="__('Subject Title')" :value="old('subject_title', $subject->subject_title)" required />

        <div>
            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Subject Types') }}</p>
            <div class="mt-3 flex flex-wrap gap-3">
                @foreach ($subjectTypes as $type)
                    <label class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700">
                        <input type="checkbox" name="subject_types[]" value="{{ $type }}" @checked($selectedSubjectTypes->contains($type))>
                        <span>{{ $type }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <flux:input name="credit_units" :label="__('Credit Units')" :value="old('credit_units', $subject->credit_units)" type="number" step="0.25" min="0.5" required />

            <flux:select name="grading_system" :label="__('Grading System')" required>
                <option value="numerical" @selected(old('grading_system', $subject->grading_system) === 'numerical')>{{ __('Numerical') }}</option>
                <option value="letter" @selected(old('grading_system', $subject->grading_system) === 'letter')>{{ __('Letter') }}</option>
            </flux:select>
        </div>

        <flux:textarea name="description" :label="__('Description')">{{ old('description', $subject->description) }}</flux:textarea>
        <flux:checkbox name="counts_toward_gpa" :label="__('Counts toward GPA')" :checked="old('counts_toward_gpa', $subject->counts_toward_gpa)" />

        <div class="flex items-center justify-end gap-3">
            <flux:button :href="route('admin.subjects.show', $subject)" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Update Subject') }}</flux:button>
        </div>
    </form>
</x-admin.layout>