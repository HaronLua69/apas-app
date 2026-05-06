<x-admin.layout :title="__('Add Semestral Distribution')" :heading="__('Add Semestral Distribution')" :subheading="__('Add a year-level and term subject distribution to '.$prospectus->title)" :backHref="route('admin.prospectuses.show', $prospectus)" :backLabel="__('Back to Prospectus')">
    <form method="POST" action="{{ route('admin.prospectuses.terms.store', $prospectus) }}" class="space-y-6">
        @csrf

        @include('admin.prospectus-terms.partials.form', [
            'prospectus' => $prospectus,
            'prospectusTerm' => null,
            'availableSubjects' => $availableSubjects,
        ])

        <div class="flex items-center justify-end gap-3">
            <flux:button :href="route('admin.prospectuses.show', $prospectus)" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Save Semestral Distribution') }}</flux:button>
        </div>
    </form>
</x-admin.layout>