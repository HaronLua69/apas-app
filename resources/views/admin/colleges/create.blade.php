<x-admin.layout :title="__('Add College')" :heading="__('Add College')" :subheading="__('Create a college record before adding departments, courses, and subjects under it.')">
    <form method="POST" action="{{ route('admin.colleges.store') }}" class="space-y-6">
        @csrf

        <flux:input name="name" :label="__('Name')" :value="old('name')" required autofocus />
        <flux:input name="abbreviation" :label="__('Abbreviation')" :value="old('abbreviation')" />
        <flux:input name="dean" :label="__('Dean')" :value="old('dean')" required />
        <flux:textarea name="description" :label="__('Description')">{{ old('description') }}</flux:textarea>

        <div class="flex items-center justify-end gap-3">
            <flux:button :href="route('admin.colleges.index')" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button variant="primary" type="submit">
                {{ __('Save College') }}
            </flux:button>
        </div>
    </form>
</x-admin.layout>