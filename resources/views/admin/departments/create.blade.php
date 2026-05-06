<x-admin.layout :title="__('Add Department')" :heading="__('Add Department')" :subheading="__('Create a department under '.$college->name)" :backHref="route('admin.colleges.show', $college)" :backLabel="__('Back to College')">
    <form method="POST" action="{{ route('admin.colleges.departments.store', $college) }}" class="space-y-6">
        @csrf
        <flux:input name="name" :label="__('Name')" :value="old('name')" required autofocus />
        <flux:input name="abbreviation" :label="__('Abbreviation')" :value="old('abbreviation')" />
        <flux:input name="chairperson" :label="__('Chairperson')" :value="old('chairperson')" required />
        <flux:textarea name="description" :label="__('Description')">{{ old('description') }}</flux:textarea>

        <div class="flex items-center justify-end gap-3">
            <flux:button :href="route('admin.colleges.show', $college)" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Save Department') }}</flux:button>
        </div>
    </form>
</x-admin.layout>