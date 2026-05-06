<x-admin.layout :title="__('Edit Classification')" :heading="__('Edit Classification')" :subheading="__('Update '.$classification->name.' for '.$classification->evaluationTemplate->title)" :backHref="route('admin.evaluation-templates.show', $classification->evaluationTemplate)" :backLabel="__('Back to Evaluation Template')">
    <form method="POST" action="{{ route('admin.classifications.update', $classification) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.evaluation-template-classifications.partials.form', [
            'evaluationTemplate' => $classification->evaluationTemplate,
            'classification' => $classification,
        ])

        <div class="flex items-center justify-end gap-3">
            <flux:button :href="route('admin.evaluation-templates.show', $classification->evaluationTemplate)" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Save Changes') }}</flux:button>
        </div>
    </form>
</x-admin.layout>