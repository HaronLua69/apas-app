@php($subjectTypes = ['Lecture', 'Laboratory', 'Field', 'Seminar'])

<x-admin.layout :title="__('Add Subject')" :heading="__('Add Subject')" :subheading="__('Create a subject under '.$department->name)" :backHref="route('admin.departments.show', ['department' => $department, 'section' => 'subjects'])" :backLabel="__('Back to Department')">
    <form method="POST" action="{{ route('admin.departments.subjects.store', $department) }}" class="space-y-6">
        @csrf
        <flux:input name="subject_code" :label="__('Subject Code')" :value="old('subject_code')" required autofocus />
        <flux:input name="subject_title" :label="__('Subject Title')" :value="old('subject_title')" required />

        <div>
            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Subject Types') }}</p>
            <div class="mt-3 flex flex-wrap gap-3">
                @foreach ($subjectTypes as $type)
                    <label class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700">
                        <input type="checkbox" name="subject_types[]" value="{{ $type }}" @checked(collect(old('subject_types', []))->contains($type))>
                        <span>{{ $type }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <flux:input name="credit_units" :label="__('Credit Units')" :value="old('credit_units')" type="number" step="0.25" min="0.5" required />

            <flux:select name="grading_system" :label="__('Grading System')" required>
                <option value="numerical" @selected(old('grading_system') === 'numerical')>{{ __('Numerical') }}</option>
                <option value="letter" @selected(old('grading_system') === 'letter')>{{ __('Letter') }}</option>
            </flux:select>
        </div>

        <flux:textarea name="description" :label="__('Description')">{{ old('description') }}</flux:textarea>
        <flux:checkbox name="counts_toward_gpa" :label="__('Counts toward GPA')" :checked="old('counts_toward_gpa', true)" />

        <div class="flex items-center justify-end gap-3">
            <flux:button :href="route('admin.departments.show', ['department' => $department, 'section' => 'subjects'])" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Save Subject') }}</flux:button>
        </div>
    </form>
</x-admin.layout>