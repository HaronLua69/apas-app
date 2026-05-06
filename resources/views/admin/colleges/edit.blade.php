<x-admin.layout :title="__('Edit College')" :heading="__('Edit College')" :subheading="__('Update the details for this college record.')">
    <form method="POST" action="{{ route('admin.colleges.update', $college) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <flux:input name="name" :label="__('Name')" :value="old('name', $college->name)" required autofocus />
        <flux:input name="abbreviation" :label="__('Abbreviation')" :value="old('abbreviation', $college->abbreviation)" />
        <flux:input name="dean" :label="__('Dean')" :value="old('dean', $college->dean)" required />
        <flux:textarea name="description" :label="__('Description')">{{ old('description', $college->description) }}</flux:textarea>

        <div class="flex items-center justify-end gap-3">
            <flux:button :href="route('admin.colleges.index')" variant="ghost" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button variant="primary" type="submit">
                {{ __('Update College') }}
            </flux:button>
        </div>
    </form>
</x-admin.layout>