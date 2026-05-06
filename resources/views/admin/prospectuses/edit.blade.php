<x-admin.layout :title="__('Edit Prospectus')" :heading="__('Edit Prospectus')" :subheading="__('Update '.$prospectus->title)" :backHref="route('admin.departments.show', $prospectus->course->department)" :backLabel="__('Back to Department')">
    <form method="POST" action="{{ route('admin.prospectuses.update', $prospectus) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.prospectuses.partials.form', [
            'course' => $prospectus->course,
            'prospectus' => $prospectus,
        ])

        <div class="flex items-center justify-end gap-3">
            <flux:button :href="route('admin.prospectuses.show', $prospectus)" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Save Changes') }}</flux:button>
        </div>
    </form>
</x-admin.layout>