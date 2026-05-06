<x-admin.layout :title="__('Edit Semestral Distribution')" :heading="__('Edit Semestral Distribution')" :subheading="__('Update '.$prospectusTerm->term_name.' for '.$prospectus->title)" :backHref="route('admin.prospectuses.show', $prospectus)" :backLabel="__('Back to Prospectus')">
    <form method="POST" action="{{ route('admin.terms.update', $prospectusTerm) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.prospectus-terms.partials.form', [
            'prospectus' => $prospectus,
            'prospectusTerm' => $prospectusTerm,
            'availableSubjects' => $availableSubjects,
        ])

        <div class="flex items-center justify-end gap-3">
            <flux:button :href="route('admin.prospectuses.show', $prospectus)" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Save Changes') }}</flux:button>
        </div>
    </form>
</x-admin.layout>