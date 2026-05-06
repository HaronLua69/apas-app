<x-admin.layout :title="__('Edit Evaluation Template')" :heading="__('Edit Evaluation Template')" :subheading="__('Update '.$evaluationTemplate->title)" :backHref="route('admin.departments.show', $evaluationTemplate->course->department)" :backLabel="__('Back to Department')">
    <form method="POST" action="{{ route('admin.evaluation-templates.update', $evaluationTemplate) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.evaluation-templates.partials.form', [
            'course' => $evaluationTemplate->course,
            'evaluationTemplate' => $evaluationTemplate,
        ])

        <div class="flex items-center justify-end gap-3">
            <flux:button :href="route('admin.evaluation-templates.show', $evaluationTemplate)" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Save Changes') }}</flux:button>
        </div>
    </form>
</x-admin.layout>