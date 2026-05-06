<x-admin.layout :title="__('Add Prospectus')" :heading="__('Add Prospectus')" :subheading="__('Create a prospectus for '.$course->name)" :backHref="route('admin.departments.show', $course->department)" :backLabel="__('Back to Department')">
    <form method="POST" action="{{ route('admin.courses.prospectuses.store', $course) }}" class="space-y-6">
        @csrf

        @include('admin.prospectuses.partials.form', [
            'course' => $course,
            'prospectus' => null,
        ])

        <div class="flex items-center justify-end gap-3">
            <flux:button :href="route('admin.departments.show', $course->department)" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Save Prospectus') }}</flux:button>
        </div>
    </form>
</x-admin.layout>