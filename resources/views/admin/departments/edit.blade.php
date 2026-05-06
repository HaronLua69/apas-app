<x-admin.layout :title="__('Edit Department')" :heading="__('Edit Department')" :subheading="__('Update the details for this department.')" :backHref="route('admin.colleges.show', $department->college)" :backLabel="__('Back to College')">
    <form method="POST" action="{{ route('admin.departments.update', $department) }}" class="space-y-6">
        @csrf
        @method('PUT')
        <flux:input name="name" :label="__('Name')" :value="old('name', $department->name)" required autofocus />
        <flux:input name="abbreviation" :label="__('Abbreviation')" :value="old('abbreviation', $department->abbreviation)" />
        <flux:input name="chairperson" :label="__('Chairperson')" :value="old('chairperson', $department->chairperson)" required />
        <flux:textarea name="description" :label="__('Description')">{{ old('description', $department->description) }}</flux:textarea>

        <div class="flex items-center justify-end gap-3">
            <flux:button :href="route('admin.departments.show', $department)" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Update Department') }}</flux:button>
        </div>
    </form>
</x-admin.layout>