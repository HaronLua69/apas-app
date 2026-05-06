<x-admin.layout :title="__('Duplicate Prospectus')" :heading="__('Duplicate Prospectus')" :subheading="__('Create a new prospectus by copying '.$sourceProspectus->title)" :backHref="route('admin.prospectuses.show', $sourceProspectus)" :backLabel="__('Back to Prospectus')">
    <form method="POST" action="{{ route('admin.prospectuses.duplicate.store', $sourceProspectus) }}" class="space-y-6">
        @csrf

        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900/60 dark:text-zinc-200">
            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Source Prospectus') }}</p>
            <p class="mt-2">{{ $sourceProspectus->title }}</p>
            <p class="mt-1 text-zinc-600 dark:text-zinc-300">{{ $sourceProspectus->major?->name ?: __('General program prospectus') }}</p>
            <p class="mt-3 text-zinc-600 dark:text-zinc-300">{{ __('All semestral distributions, assigned subjects, and elective placeholders from the source prospectus will be copied into the new prospectus.') }}</p>
        </div>

        @include('admin.prospectuses.partials.form', [
            'course' => $course,
            'prospectus' => (object) [
                'title' => old('title', $sourceProspectus->title.' (Copy)'),
                'major_id' => old('major_id', $sourceProspectus->major_id),
                'description' => old('description', $sourceProspectus->description),
                'is_active' => old('is_active', false),
            ],
        ])

        <div class="flex items-center justify-end gap-3">
            <flux:button :href="route('admin.prospectuses.show', $sourceProspectus)" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Duplicate Prospectus') }}</flux:button>
        </div>
    </form>
</x-admin.layout>