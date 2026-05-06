<x-admin.layout :title="__('Add Classification')" :heading="__('Add Classification')" :subheading="__('Add a classification to '.$evaluationTemplate->title)" :backHref="route('admin.evaluation-templates.show', $evaluationTemplate)" :backLabel="__('Back to Evaluation Template')">
    <form method="POST" action="{{ route('admin.evaluation-templates.classifications.store', $evaluationTemplate) }}" class="space-y-6">
        @csrf

        @include('admin.evaluation-template-classifications.partials.form', [
            'evaluationTemplate' => $evaluationTemplate,
            'classification' => null,
        ])

        <div class="flex items-center justify-end gap-3">
            <flux:button :href="route('admin.evaluation-templates.show', $evaluationTemplate)" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Save Classification') }}</flux:button>
        </div>
    </form>
</x-admin.layout>