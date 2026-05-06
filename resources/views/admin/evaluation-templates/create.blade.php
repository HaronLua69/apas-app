<x-admin.layout :title="__('Add Evaluation Template')" :heading="__('Add Evaluation Template')" :subheading="__('Create an evaluation template for '.$course->name)" :backHref="route('admin.departments.show', $course->department)" :backLabel="__('Back to Department')">
    <form method="POST" action="{{ route('admin.courses.evaluation-templates.store', $course) }}" class="space-y-6">
        @csrf

        @include('admin.evaluation-templates.partials.form', [
            'course' => $course,
            'evaluationTemplate' => null,
        ])

        <div class="flex items-center justify-end gap-3">
            <flux:button :href="route('admin.departments.show', $course->department)" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Save Evaluation Template') }}</flux:button>
        </div>
    </form>
</x-admin.layout>